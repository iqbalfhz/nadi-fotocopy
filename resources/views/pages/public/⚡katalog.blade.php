<?php

use App\Models\Category;
use App\Models\Product;
use App\Support\Rupiah;
use App\Support\StoreProfile;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Katalog & Harga')] class extends Component {
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'kategori', except: '')]
    public string $category = '';

    /**
     * Kategori yang punya minimal satu produk aktif.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()
            ->whereHas('products', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function products(): Collection
    {
        return Product::query()
            ->active()
            ->with('category')
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->when(
                $this->category !== '',
                fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $this->category)),
            )
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<string, Collection<int, Product>>
     */
    #[Computed]
    public function grouped(): Collection
    {
        return $this->products()->groupBy(fn (Product $product) => $product->category->name);
    }

    #[Computed]
    public function isFiltered(): bool
    {
        return $this->search !== '' || $this->category !== '';
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->category = '';
    }

    public function selectCategory(string $slug): void
    {
        $this->category = $this->category === $slug ? '' : $slug;
    }

    public function rupiah(int $amount): string
    {
        return Rupiah::format($amount);
    }

    /**
     * Link WA yang sudah menyebut nama produknya.
     */
    public function productWaLink(Product $product): ?string
    {
        return StoreProfile::whatsappLink(
            'Halo '.StoreProfile::name().', saya mau tanya soal "'.$product->name.'".',
        );
    }
}; ?>

<div>
    {{-- ============ Judul halaman + pencarian ============ --}}
    <section class="relative overflow-hidden border-b border-zinc-200 dark:border-zinc-800">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-b from-emerald-50 to-white dark:from-emerald-950/20 dark:to-zinc-950"></div>

        <div class="mx-auto max-w-6xl px-4 py-12">
            <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Katalog &amp; Harga</h1>
            <p class="mt-2 max-w-xl text-pretty text-zinc-600 dark:text-zinc-400">
                Semua layanan dan produk beserta harganya. Pemesanan dan pembayaran dilakukan langsung di toko.
            </p>

            <div class="mt-7 max-w-xl">
                <div class="relative">
                    <svg class="pointer-events-none absolute start-4 top-1/2 size-5 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>

                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari fotocopy, jilid, kertas..."
                        aria-label="Cari layanan atau produk"
                        class="w-full rounded-2xl border border-zinc-200 bg-white py-3.5 ps-12 pe-12 text-base shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-zinc-800 dark:bg-zinc-900"
                    >

                    <div wire:loading wire:target="search" class="absolute end-4 top-1/2 -translate-y-1/2">
                        <svg class="size-5 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4Z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ Filter kategori ============ --}}
    <div class="sticky top-[57px] z-30 border-b border-zinc-200 bg-white/90 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/90">
        <div class="mx-auto max-w-6xl px-4">
            <div class="flex items-center gap-2 overflow-x-auto py-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <button
                    type="button"
                    wire:click="clearFilters"
                    @class([
                        'shrink-0 rounded-full px-4 py-1.5 text-sm font-medium transition',
                        'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $category === '',
                        'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800' => $category !== '',
                    ])
                >
                    Semua
                </button>

                @foreach ($this->categories() as $item)
                    <button
                        type="button"
                        wire:key="chip-{{ $item->id }}"
                        wire:click="selectCategory('{{ $item->slug }}')"
                        @class([
                            'shrink-0 rounded-full px-4 py-1.5 text-sm font-medium transition',
                            'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $category === $item->slug,
                            'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800' => $category !== $item->slug,
                        ])
                    >
                        {{ $item->name }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ============ Daftar produk ============ --}}
    <main class="mx-auto max-w-6xl px-4 py-10">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-zinc-500">
                Menampilkan <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $this->products()->count() }}</span> layanan &amp; produk
                @if ($search !== '')
                    untuk &ldquo;<span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $search }}</span>&rdquo;
                @endif
            </p>

            @if ($this->isFiltered())
                <button
                    type="button"
                    wire:click="clearFilters"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 transition hover:text-emerald-800 dark:text-emerald-400"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                    Hapus filter
                </button>
            @endif
        </div>

        <div wire:loading.class="opacity-40" wire:target="search,selectCategory,clearFilters" class="transition-opacity">
            @forelse ($this->grouped() as $categoryName => $items)
                <section class="mb-10" wire:key="grup-{{ $loop->index }}">
                    <div class="mb-4 flex items-center gap-3">
                        <h2 class="text-lg font-semibold tracking-tight">{{ $categoryName }}</h2>
                        <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-500 dark:bg-zinc-900">{{ $items->count() }}</span>
                        <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-800"></div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($items as $product)
                            @php
                                $productWa = $this->productWaLink($product);
                            @endphp

                            <article
                                wire:key="produk-{{ $product->id }}"
                                class="group relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-5 transition duration-200 hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-lg hover:shadow-emerald-900/5 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-emerald-800"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="font-medium text-pretty">{{ $product->name }}</h3>

                                    {{-- Hanya status, tidak pernah angka stok (§7.2) --}}
                                    @if ($product->isAvailable())
                                        <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                                            <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                            Tersedia
                                        </span>
                                    @else
                                        <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                            <span class="size-1.5 rounded-full bg-zinc-400"></span>
                                            Habis
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-3 flex items-baseline gap-1">
                                    <span class="text-2xl font-semibold tracking-tight tabular-nums">{{ $this->rupiah($product->price) }}</span>
                                    <span class="text-sm text-zinc-500">/ {{ $product->unit }}</span>
                                </div>

                                @if ($productWa)
                                    <a
                                        href="{{ $productWa }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl border border-zinc-200 px-3 py-2.5 text-sm font-medium transition group-hover:border-emerald-600 group-hover:bg-emerald-600 group-hover:text-white dark:border-zinc-700"
                                    >
                                        <x-wa-icon class="size-4" />
                                        Tanya via WhatsApp
                                    </a>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="rounded-2xl border border-dashed border-zinc-300 px-6 py-16 text-center dark:border-zinc-700">
                    <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-900">
                        <svg class="size-6 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 font-medium">Tidak ada yang cocok</h3>
                    <p class="mt-1 text-sm text-zinc-500">Coba kata kunci lain, atau tanyakan langsung lewat WhatsApp.</p>
                    @if ($this->isFiltered())
                        <button type="button" wire:click="clearFilters" class="mt-4 rounded-xl bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
                            Tampilkan semua
                        </button>
                    @endif
                </div>
            @endforelse
        </div>
    </main>
</div>
