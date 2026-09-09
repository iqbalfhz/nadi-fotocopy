<?php

use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Support\Rupiah;
use App\Support\StoreProfile;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Beranda')] class extends Component {
    /**
     * Kategori yang punya produk aktif, untuk kartu "layanan kami".
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()
            ->whereHas('products', fn ($q) => $q->where('is_active', true))
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take(6)
            ->get();
    }

    /**
     * Beberapa layanan termurah sebagai contoh harga di beranda.
     *
     * @return Collection<int, Product>
     */
    #[Computed]
    public function popularServices(): Collection
    {
        return Product::query()
            ->active()
            ->where('type', ProductType::Jasa)
            ->orderBy('price')
            ->take(4)
            ->get();
    }

    public function rupiah(int $amount): string
    {
        return Rupiah::format($amount);
    }

    public function waLink(): ?string
    {
        return StoreProfile::whatsappLink('Halo '.StoreProfile::name().', saya mau bertanya.');
    }

    public function hours(): ?string
    {
        return StoreProfile::hours();
    }

    public function address(): ?string
    {
        return StoreProfile::address();
    }
}; ?>

<div>
    {{-- ============ Hero ============ --}}
    <section class="relative overflow-hidden border-b border-zinc-200 dark:border-zinc-800">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-b from-emerald-50 via-white to-white dark:from-emerald-950/30 dark:via-zinc-950 dark:to-zinc-950"></div>
        <div class="pointer-events-none absolute -top-24 -right-24 -z-10 size-72 rounded-full bg-emerald-400/20 blur-3xl dark:bg-emerald-500/10"></div>
        <div class="pointer-events-none absolute -bottom-32 -left-20 -z-10 size-72 rounded-full bg-sky-400/10 blur-3xl dark:bg-sky-500/10"></div>

        <div class="mx-auto max-w-6xl px-4 py-16 sm:py-24">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-400">
                    <span class="relative flex size-1.5">
                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-500 opacity-75"></span>
                        <span class="relative inline-flex size-1.5 rounded-full bg-emerald-600"></span>
                    </span>
                    Harga selalu diperbarui
                </span>

                <h1 class="mt-4 text-4xl font-semibold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                    Fotocopy, print, & alat tulis
                    <span class="bg-gradient-to-r from-emerald-600 to-teal-500 bg-clip-text text-transparent">tanpa perlu bolak-balik</span>
                </h1>

                <p class="mt-5 text-lg text-pretty text-zinc-600 dark:text-zinc-400">
                    Cek harga dan ketersediaan langsung dari HP sebelum berangkat. Kalau sudah cocok,
                    tinggal chat WhatsApp atau datang ke toko.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a
                        href="{{ route('katalog') }}"
                        wire:navigate
                        class="inline-flex items-center gap-2 rounded-xl bg-zinc-900 px-5 py-3 text-sm font-medium text-white shadow-sm transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                    >
                        Lihat katalog & harga
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </a>

                    @if ($this->waLink())
                        <a
                            href="{{ $this->waLink() }}"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 px-5 py-3 text-sm font-medium transition hover:border-emerald-600 hover:text-emerald-700 dark:border-zinc-700 dark:hover:text-emerald-400"
                        >
                            <x-wa-icon class="size-4" />
                            Chat WhatsApp
                        </a>
                    @endif
                </div>

                <div class="mt-8 flex flex-wrap items-center gap-2 text-sm">
                    @if ($this->hours())
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-100 px-3 py-1.5 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                            <svg class="size-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            {{ $this->hours() }}
                        </span>
                    @endif
                    @if ($this->address())
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-100 px-3 py-1.5 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                            <svg class="size-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                            </svg>
                            {{ $this->address() }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ============ Layanan kami ============ --}}
    <section class="mx-auto max-w-6xl px-4 py-16">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight">Yang kami kerjakan</h2>
                <p class="mt-1 text-zinc-600 dark:text-zinc-400">Dari selembar fotocopy sampai kebutuhan alat tulis kantor.</p>
            </div>
            <a href="{{ route('katalog') }}" wire:navigate class="text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">
                Lihat semua &rarr;
            </a>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($this->categories() as $category)
                <a
                    href="{{ route('katalog', ['kategori' => $category->slug]) }}"
                    wire:navigate
                    wire:key="kat-{{ $category->id }}"
                    class="group rounded-2xl border border-zinc-200 bg-white p-6 transition duration-200 hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-lg hover:shadow-emerald-900/5 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-emerald-800"
                >
                    <span class="flex size-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75V4.5a1.5 1.5 0 0 0-1.5-1.5H6.75a1.5 1.5 0 0 0-1.5 1.5v2.25M6.75 17.25V19.5a1.5 1.5 0 0 0 1.5 1.5h7.5a1.5 1.5 0 0 0 1.5-1.5v-2.25M4.5 6.75h15a1.5 1.5 0 0 1 1.5 1.5v6a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 14.25v-6a1.5 1.5 0 0 1 1.5-1.5Z" />
                        </svg>
                    </span>

                    <h3 class="mt-4 font-medium">{{ $category->name }}</h3>
                    <p class="mt-1 text-sm text-zinc-500">{{ $category->products_count }} pilihan</p>

                    <span class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-emerald-700 dark:text-emerald-400">
                        Lihat harga
                        <svg class="size-4 transition group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </span>
                </a>
            @empty
                <p class="text-zinc-500">Katalog belum diisi.</p>
            @endforelse
        </div>
    </section>

    {{-- ============ Kenapa kami ============ --}}
    <section class="border-y border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900/40">
        <div class="mx-auto max-w-6xl px-4 py-16">
            <h2 class="text-2xl font-semibold tracking-tight">Kenapa pelanggan balik lagi</h2>

            <div class="mt-8 grid gap-6 sm:grid-cols-3">
                <div>
                    <span class="flex size-11 items-center justify-center rounded-xl bg-white text-emerald-600 shadow-sm dark:bg-zinc-900 dark:text-emerald-400">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                    <h3 class="mt-4 font-medium">Cepat ditunggu</h3>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        Fotocopy dan print langsung dikerjakan. Tidak perlu bolak-balik ambil hasil.
                    </p>
                </div>

                <div>
                    <span class="flex size-11 items-center justify-center rounded-xl bg-white text-emerald-600 shadow-sm dark:bg-zinc-900 dark:text-emerald-400">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                    <h3 class="mt-4 font-medium">Harga transparan</h3>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        Semua harga terpampang di katalog dan selalu diperbarui. Tidak ada biaya kejutan.
                    </p>
                </div>

                <div>
                    <span class="flex size-11 items-center justify-center rounded-xl bg-white text-emerald-600 shadow-sm dark:bg-zinc-900 dark:text-emerald-400">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76V21a.75.75 0 0 0 .75.75h18a.75.75 0 0 0 .75-.75v-8.24M2.25 12.76 12 3l9.75 9.76M9 21v-6h6v6" />
                        </svg>
                    </span>
                    <h3 class="mt-4 font-medium">Bisa tanya dulu</h3>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        Ragu soal stok atau ukuran? Chat WhatsApp dulu sebelum datang ke toko.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ Contoh harga ============ --}}
    @if ($this->popularServices()->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 py-16">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight">Layanan paling sering dicari</h2>
                    <p class="mt-1 text-zinc-600 dark:text-zinc-400">Harga mulai dari yang paling terjangkau.</p>
                </div>
                <a href="{{ route('katalog') }}" wire:navigate class="text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">
                    Katalog lengkap &rarr;
                </a>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($this->popularServices() as $service)
                    <div wire:key="pop-{{ $service->id }}" class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                        <h3 class="text-sm font-medium text-pretty">{{ $service->name }}</h3>
                        <div class="mt-3 flex items-baseline gap-1">
                            <span class="text-2xl font-semibold tracking-tight tabular-nums">{{ $this->rupiah($service->price) }}</span>
                            <span class="text-sm text-zinc-500">/ {{ $service->unit }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ============ CTA ============ --}}
    <section class="mx-auto max-w-6xl px-4 pb-20">
        <div class="relative overflow-hidden rounded-3xl bg-zinc-900 px-6 py-14 text-center dark:bg-zinc-900">
            <div class="pointer-events-none absolute -top-20 -right-16 size-64 rounded-full bg-emerald-500/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 -left-10 size-64 rounded-full bg-teal-500/10 blur-3xl"></div>

            <h2 class="text-2xl font-semibold tracking-tight text-balance text-white sm:text-3xl">
                Ada yang mau ditanyakan dulu?
            </h2>
            <p class="mx-auto mt-3 max-w-lg text-pretty text-zinc-400">
                Tanya stok, ukuran, atau estimasi biaya lewat WhatsApp. Kami balas secepatnya di jam operasional.
            </p>

            <div class="mt-7 flex flex-wrap justify-center gap-3">
                @if ($this->waLink())
                    <a
                        href="{{ $this->waLink() }}"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-medium text-white transition hover:bg-emerald-700"
                    >
                        <x-wa-icon class="size-4" />
                        Chat WhatsApp
                    </a>
                @endif
                <a
                    href="{{ route('kontak') }}"
                    wire:navigate
                    class="inline-flex items-center gap-2 rounded-xl border border-zinc-700 px-5 py-3 text-sm font-medium text-white transition hover:bg-zinc-800"
                >
                    Lihat lokasi toko
                </a>
            </div>
        </div>
    </section>
</div>
