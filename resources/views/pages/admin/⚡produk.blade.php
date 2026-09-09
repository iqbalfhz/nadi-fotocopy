<?php

use App\Actions\Pos\AdjustStock;
use App\Actions\Pos\RecordStockMovement;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Exceptions\PosException;
use App\Models\Category;
use App\Models\Product;
use App\Support\Rupiah;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Kelola Produk')] class extends Component {
    use WithPagination;

    public string $search = '';

    public ?int $filterCategory = null;

    // Form produk
    public ?int $editingId = null;

    public string $name = '';

    public ?int $categoryId = null;

    public int $price = 0;

    public string $unit = 'pcs';

    public string $type = 'produk';

    public bool $isActive = true;

    public int $initialStock = 0;

    // Form penyesuaian stok
    public ?int $adjustingId = null;

    public int $newStock = 0;

    public string $stockReason = '';

    #[Computed]
    public function categories()
    {
        return Category::query()->orderBy('sort_order')->orderBy('name')->get();
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->with('category')
            ->when($this->filterCategory, fn ($q) => $q->where('category_id', $this->filterCategory))
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function adjustingProduct(): ?Product
    {
        return $this->adjustingId ? Product::query()->find($this->adjustingId) : null;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCategory(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        Flux::modal('form-produk')->show();
    }

    public function edit(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);

        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->categoryId = $product->category_id;
        $this->price = $product->price;
        $this->unit = $product->unit;
        $this->type = $product->type->value;
        $this->isActive = $product->is_active;
        $this->initialStock = 0;

        Flux::modal('form-produk')->show();
    }

    public function save(RecordStockMovement $stock): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'categoryId' => ['required', 'exists:categories,id'],
            'price' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
            'type' => ['required', Rule::enum(ProductType::class)],
            'initialStock' => ['integer', 'min:0'],
        ]);

        $type = ProductType::from($validated['type']);

        DB::transaction(function () use ($validated, $type, $stock) {
            if ($this->editingId) {
                $product = Product::query()->findOrFail($this->editingId);
                $product->update([
                    'name' => $validated['name'],
                    'slug' => $this->uniqueSlug($validated['name'], $product->id),
                    'category_id' => $validated['categoryId'],
                    'price' => $validated['price'],
                    'unit' => $validated['unit'],
                    'type' => $type,
                    'track_stock' => $type->tracksStockByDefault(),
                    'is_active' => $this->isActive,
                ]);

                return;
            }

            // Stok awal masuk lewat StockMovement, bukan diisi langsung (§7.2).
            $product = Product::create([
                'name' => $validated['name'],
                'slug' => $this->uniqueSlug($validated['name']),
                'category_id' => $validated['categoryId'],
                'price' => $validated['price'],
                'stock' => 0,
                'unit' => $validated['unit'],
                'type' => $type,
                'track_stock' => $type->tracksStockByDefault(),
                'is_active' => $this->isActive,
            ]);

            if ($validated['initialStock'] > 0) {
                $stock->handle(
                    product: $product,
                    qtyChange: $validated['initialStock'],
                    type: StockMovementType::StokMasuk,
                    actor: Auth::user(),
                    note: 'Stok awal saat produk dibuat',
                );
            }
        });

        Flux::modal('form-produk')->close();
        Flux::toast(variant: 'success', text: $this->editingId ? 'Produk diperbarui.' : 'Produk ditambahkan.');

        $this->resetForm();
        unset($this->products);
    }

    public function toggleActive(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);
        $product->update(['is_active' => ! $product->is_active]);

        unset($this->products);
    }

    public function confirmAdjust(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);

        $this->adjustingId = $product->id;
        $this->newStock = $product->stock;
        $this->stockReason = '';

        Flux::modal('sesuaikan-stok')->show();
    }

    public function adjust(AdjustStock $adjuster): void
    {
        $product = Product::query()->findOrFail($this->adjustingId);

        try {
            $adjuster->handle($product, $this->newStock, Auth::user(), $this->stockReason);
        } catch (PosException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        Flux::modal('sesuaikan-stok')->close();
        Flux::toast(variant: 'success', text: 'Stok disesuaikan dan tercatat.');

        $this->adjustingId = null;
        $this->stockReason = '';
        unset($this->products);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Product::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->categoryId = $this->categories()->first()?->id;
        $this->price = 0;
        $this->unit = 'pcs';
        $this->type = 'produk';
        $this->isActive = true;
        $this->initialStock = 0;
        $this->resetValidation();
    }

    public function rupiah(int $amount): string
    {
        return Rupiah::format($amount);
    }

    public function statusColor(Product $product): string
    {
        return $product->is_active ? 'green' : 'zinc';
    }
}; ?>

<div class="flex flex-col gap-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="flex flex-wrap gap-2">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Cari produk..." class="w-64" />
            <flux:select wire:model.live="filterCategory" class="w-52">
                <flux:select.option value="">Semua kategori</flux:select.option>
                @foreach ($this->categories() as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">Produk baru</flux:button>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-200 dark:border-zinc-700">
                <tr class="text-zinc-500">
                    <th class="p-3 text-start font-medium">Nama</th>
                    <th class="p-3 text-start font-medium">Kategori</th>
                    <th class="p-3 text-end font-medium">Harga</th>
                    <th class="p-3 text-start font-medium">Stok</th>
                    <th class="p-3 text-start font-medium">Status</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->products() as $product)
                    <tr wire:key="p-{{ $product->id }}" class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                        <td class="p-3">
                            <div class="font-medium">{{ $product->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $product->type->label() }} · per {{ $product->unit }}</div>
                        </td>
                        <td class="p-3">{{ $product->category->name }}</td>
                        <td class="p-3 text-end tabular-nums">{{ $this->rupiah($product->price) }}</td>
                        <td class="p-3">
                            @if ($product->track_stock)
                                <span class="tabular-nums">{{ $product->stock }}</span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="p-3">
                            <flux:badge size="sm" :color="$this->statusColor($product)">
                                {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                            </flux:badge>
                        </td>
                        <td class="p-3">
                            <div class="flex justify-end gap-2">
                                @if ($product->track_stock)
                                    <flux:button size="sm" variant="subtle" wire:click="confirmAdjust({{ $product->id }})">Stok</flux:button>
                                @endif
                                <flux:button size="sm" variant="subtle" wire:click="edit({{ $product->id }})">Ubah</flux:button>
                                <flux:button size="sm" variant="subtle" wire:click="toggleActive({{ $product->id }})">
                                    {{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center text-zinc-500">Belum ada produk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->products()->links() }}

    <flux:modal name="form-produk" class="md:w-[32rem]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Ubah produk' : 'Produk baru' }}</flux:heading>

            <flux:input wire:model="name" label="Nama" placeholder="Contoh: Fotocopy Hitam Putih A4" />

            <flux:select wire:model="categoryId" label="Kategori">
                @foreach ($this->categories() as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid grid-cols-2 gap-3">
                <flux:input type="number" min="0" wire:model="price" label="Harga (Rp)" />
                <flux:input wire:model="unit" label="Satuan" placeholder="lembar / pcs / rim" />
            </div>

            <flux:select wire:model.live="type" label="Jenis">
                <flux:select.option value="produk">Produk (punya stok)</flux:select.option>
                <flux:select.option value="jasa">Jasa (tanpa stok)</flux:select.option>
            </flux:select>

            @if (! $editingId && $type === 'produk')
                <flux:input type="number" min="0" wire:model="initialStock" label="Stok awal" description="Dicatat sebagai stok masuk." />
            @endif

            @if ($editingId)
                <flux:text class="text-xs">
                    Stok tidak diubah dari sini — gunakan tombol "Stok" agar perubahannya tercatat.
                </flux:text>
            @endif

            <flux:checkbox wire:model="isActive" label="Aktif (tampil di katalog & kasir)" />

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button variant="primary" wire:click="save">Simpan</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="sesuaikan-stok" class="md:w-96">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Sesuaikan stok</flux:heading>
                @if ($this->adjustingProduct())
                    <flux:text class="mt-1">
                        {{ $this->adjustingProduct()->name }} — stok sekarang {{ $this->adjustingProduct()->stock }}.
                    </flux:text>
                @endif
            </div>

            <flux:input type="number" min="0" wire:model="newStock" label="Stok baru" />
            <flux:input wire:model="stockReason" label="Alasan" placeholder="Contoh: stok masuk dari supplier" />

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button variant="primary" wire:click="adjust">Simpan</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
