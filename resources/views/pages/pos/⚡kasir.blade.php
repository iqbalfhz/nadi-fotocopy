<?php

use App\Actions\Pos\CalculateDiscount;
use App\Actions\Pos\CartLine;
use App\Actions\Pos\RecordSale;
use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Exceptions\PosException;
use App\Models\Category;
use App\Models\Product;
use App\Support\Rupiah;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Kasir')] class extends Component {
    /**
     * Isi keranjang: product_id => qty.
     *
     * @var array<int, int>
     */
    public array $cart = [];

    public string $search = '';

    public ?int $categoryId = null;

    public string $paymentMethod = 'cash';

    public ?string $discountType = null;

    public int $discountValue = 0;

    public int $paidAmount = 0;

    public string $customerPhone = '';

    /**
     * @return \Illuminate\Support\Collection<int, Category>
     */
    #[Computed]
    public function categories()
    {
        return Category::query()->orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Product>
     */
    #[Computed]
    public function products()
    {
        return Product::query()
            ->active()
            ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->get();
    }

    /**
     * Baris keranjang lengkap dengan harga dari database.
     *
     * @return array<int, array{product: Product, qty: int, subtotal: int}>
     */
    #[Computed]
    public function lines(): array
    {
        if ($this->cart === []) {
            return [];
        }

        $products = Product::query()->whereIn('id', array_keys($this->cart))->get()->keyBy('id');

        $lines = [];

        foreach ($this->cart as $productId => $qty) {
            $product = $products->get($productId);

            if ($product) {
                $lines[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'subtotal' => $product->price * $qty,
                ];
            }
        }

        return $lines;
    }

    #[Computed]
    public function subtotal(): int
    {
        return array_sum(array_column($this->lines(), 'subtotal'));
    }

    #[Computed]
    public function discountAmount(): int
    {
        try {
            return app(CalculateDiscount::class)->handle(
                $this->subtotal(),
                $this->discountType ? DiscountType::from($this->discountType) : null,
                $this->discountValue,
            );
        } catch (PosException) {
            return 0;
        }
    }

    #[Computed]
    public function total(): int
    {
        return $this->subtotal() - $this->discountAmount();
    }

    #[Computed]
    public function change(): int
    {
        return max(0, $this->paidAmount - $this->total());
    }

    #[Computed]
    public function needsCash(): bool
    {
        return PaymentMethod::from($this->paymentMethod)->needsCashInput();
    }

    public function isCartEmpty(): bool
    {
        return $this->cart === [];
    }

    /**
     * Warna badge stok — dihitung di PHP, bukan lewat ternary di dalam
     * atribut Blade (operator ">" di sana memutus tag komponen Flux).
     */
    public function stockBadgeColor(Product $product): string
    {
        return $product->isAvailable() ? 'zinc' : 'red';
    }

    public function addToCart(int $productId): void
    {
        $product = Product::query()->active()->find($productId);

        if (! $product) {
            Flux::toast(variant: 'danger', text: 'Produk tidak ditemukan atau nonaktif.');

            return;
        }

        $newQty = ($this->cart[$productId] ?? 0) + 1;

        if (! $product->hasEnoughStock($newQty)) {
            Flux::toast(variant: 'warning', text: "Stok {$product->name} tinggal {$product->stock}.");

            return;
        }

        $this->cart[$productId] = $newQty;
    }

    public function updateQty(int $productId, int $qty): void
    {
        if ($qty < 1) {
            $this->removeLine($productId);

            return;
        }

        $product = Product::query()->find($productId);

        if ($product && ! $product->hasEnoughStock($qty)) {
            Flux::toast(variant: 'warning', text: "Stok {$product->name} hanya {$product->stock}.");
            $this->cart[$productId] = $product->stock;

            return;
        }

        $this->cart[$productId] = $qty;
    }

    public function removeLine(int $productId): void
    {
        unset($this->cart[$productId]);
    }

    public function resetCart(): void
    {
        $this->cart = [];
        $this->discountType = null;
        $this->discountValue = 0;
        $this->paidAmount = 0;
        $this->customerPhone = '';
        $this->paymentMethod = 'cash';
    }

    /**
     * Uang pas — isi otomatis sebesar total.
     */
    public function payExact(): void
    {
        $this->paidAmount = $this->total();
    }

    public function checkout(RecordSale $sale): void
    {
        $lines = array_map(
            fn ($productId, $qty) => new CartLine((int) $productId, (int) $qty),
            array_keys($this->cart),
            $this->cart,
        );

        try {
            $transaction = $sale->handle(
                cashier: Auth::user(),
                lines: $lines,
                paymentMethod: PaymentMethod::from($this->paymentMethod),
                discountType: $this->discountType ? DiscountType::from($this->discountType) : null,
                discountValue: $this->discountValue,
                paidAmount: $this->paidAmount,
                customerPhone: $this->customerPhone !== '' ? $this->customerPhone : null,
            );
        } catch (PosException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        $this->resetCart();

        $this->redirectRoute('pos.struk', ['transaction' => $transaction->id], navigate: true);
    }

    public function rupiah(int $amount): string
    {
        return Rupiah::format($amount);
    }
}; ?>

<div class="flex flex-col gap-4 lg:h-[calc(100vh-6rem)] lg:flex-row">
    {{-- Katalog produk --}}
    <div class="flex min-h-0 flex-1 flex-col gap-3">
        <div class="flex flex-col gap-2 sm:flex-row">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Cari produk atau layanan..."
                class="flex-1"
            />
            <flux:select wire:model.live="categoryId" placeholder="Semua kategori" class="sm:w-56">
                <flux:select.option value="">Semua kategori</flux:select.option>
                @foreach ($this->categories() as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
            @if ($this->products()->isEmpty())
                <div class="flex h-full items-center justify-center">
                    <flux:text>Tidak ada produk yang cocok.</flux:text>
                </div>
            @else
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                    @foreach ($this->products() as $product)
                        <button
                            type="button"
                            wire:key="produk-{{ $product->id }}"
                            wire:click="addToCart({{ $product->id }})"
                            @disabled(! $product->isAvailable())
                            class="flex flex-col gap-1 rounded-lg border border-zinc-200 p-3 text-start transition hover:border-zinc-400 disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-700 dark:hover:border-zinc-500"
                        >
                            <span class="line-clamp-2 text-sm font-medium">{{ $product->name }}</span>
                            <span class="text-xs text-zinc-500">{{ $this->rupiah($product->price) }} / {{ $product->unit }}</span>
                            @if ($product->track_stock)
                                <flux:badge size="sm" :color="$this->stockBadgeColor($product)">
                                    Stok {{ $product->stock }}
                                </flux:badge>
                            @else
                                <flux:badge size="sm" color="green">Jasa</flux:badge>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Keranjang & pembayaran --}}
    <div class="flex w-full flex-col rounded-xl border border-zinc-200 lg:w-[26rem] dark:border-zinc-700">
        <div class="flex items-center justify-between border-b border-zinc-200 p-3 dark:border-zinc-700">
            <flux:heading size="lg">Keranjang</flux:heading>
            @if ($this->lines() !== [])
                <flux:button size="sm" variant="subtle" wire:click="resetCart">Kosongkan</flux:button>
            @endif
        </div>

        <div class="min-h-24 flex-1 overflow-y-auto p-3">
            @forelse ($this->lines() as $line)
                <div wire:key="baris-{{ $line['product']->id }}" class="flex items-start gap-2 border-b border-zinc-100 py-2 last:border-0 dark:border-zinc-800">
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium">{{ $line['product']->name }}</div>
                        <div class="text-xs text-zinc-500">{{ $this->rupiah($line['product']->price) }} / {{ $line['product']->unit }}</div>
                    </div>
                    <div class="flex items-center gap-1">
                        <flux:button size="xs" variant="subtle" icon="minus" wire:click="updateQty({{ $line['product']->id }}, {{ $line['qty'] - 1 }})" />
                        <span class="w-8 text-center text-sm tabular-nums">{{ $line['qty'] }}</span>
                        <flux:button size="xs" variant="subtle" icon="plus" wire:click="updateQty({{ $line['product']->id }}, {{ $line['qty'] + 1 }})" />
                    </div>
                    <div class="w-24 text-end text-sm tabular-nums">{{ $this->rupiah($line['subtotal']) }}</div>
                </div>
            @empty
                <div class="flex h-full items-center justify-center py-8">
                    <flux:text>Klik produk untuk menambah.</flux:text>
                </div>
            @endforelse
        </div>

        <div class="space-y-3 border-t border-zinc-200 p-3 dark:border-zinc-700">
            <div class="flex gap-2">
                <flux:select wire:model.live="discountType" class="flex-1">
                    <flux:select.option value="">Tanpa diskon</flux:select.option>
                    <flux:select.option value="nominal">Diskon Rp</flux:select.option>
                    <flux:select.option value="persen">Diskon %</flux:select.option>
                </flux:select>
                @if ($discountType)
                    <flux:input type="number" min="0" wire:model.live="discountValue" class="w-28" />
                @endif
            </div>

            <div class="space-y-1 text-sm">
                <div class="flex justify-between">
                    <span class="text-zinc-500">Subtotal</span>
                    <span class="tabular-nums">{{ $this->rupiah($this->subtotal()) }}</span>
                </div>
                @if ($this->discountAmount() > 0)
                    <div class="flex justify-between text-emerald-600 dark:text-emerald-400">
                        <span>Diskon</span>
                        <span class="tabular-nums">− {{ $this->rupiah($this->discountAmount()) }}</span>
                    </div>
                @endif
                <div class="flex justify-between border-t border-zinc-200 pt-1 text-base font-semibold dark:border-zinc-700">
                    <span>Total</span>
                    <span class="tabular-nums">{{ $this->rupiah($this->total()) }}</span>
                </div>
            </div>

            <flux:radio.group wire:model.live="paymentMethod" variant="segmented" class="w-full">
                <flux:radio value="cash">Cash</flux:radio>
                <flux:radio value="qris">QRIS</flux:radio>
                <flux:radio value="debit">Debit</flux:radio>
            </flux:radio.group>

            @if ($this->needsCash())
                <div class="flex gap-2">
                    <flux:input type="number" min="0" wire:model.live="paidAmount" placeholder="Uang diterima" class="flex-1" />
                    <flux:button variant="subtle" wire:click="payExact">Uang pas</flux:button>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-500">Kembalian</span>
                    <span class="font-medium tabular-nums">{{ $this->rupiah($this->change()) }}</span>
                </div>
            @endif

            <flux:input wire:model="customerPhone" placeholder="No. WA pelanggan (opsional)" />

            <flux:button
                variant="primary"
                class="w-full"
                wire:click="checkout"
                wire:loading.attr="disabled"
                :disabled="$this->isCartEmpty()"
            >
                Simpan & Cetak Struk
            </flux:button>
        </div>
    </div>
</div>
