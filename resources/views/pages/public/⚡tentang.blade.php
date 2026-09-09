<?php

use App\Models\Category;
use App\Models\Product;
use App\Support\StoreProfile;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Tentang Kami')] class extends Component {
    #[Computed]
    public function productCount(): int
    {
        return Product::query()->active()->count();
    }

    #[Computed]
    public function categoryCount(): int
    {
        return Category::query()
            ->whereHas('products', fn ($q) => $q->where('is_active', true))
            ->count();
    }

    public function storeName(): string
    {
        return StoreProfile::name();
    }

    public function hours(): ?string
    {
        return StoreProfile::hours();
    }

    public function waLink(): ?string
    {
        return StoreProfile::whatsappLink('Halo '.StoreProfile::name().', saya mau bertanya.');
    }
}; ?>

<div>
    <section class="relative overflow-hidden border-b border-zinc-200 dark:border-zinc-800">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-b from-emerald-50 to-white dark:from-emerald-950/20 dark:to-zinc-950"></div>

        <div class="mx-auto max-w-6xl px-4 py-12">
            <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Tentang {{ $this->storeName() }}</h1>
            <p class="mt-2 max-w-2xl text-pretty text-zinc-600 dark:text-zinc-400">
                Toko fotocopy dan alat tulis yang dikelola langsung oleh pemiliknya.
            </p>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-14">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="max-w-2xl space-y-4 text-zinc-700 dark:text-zinc-300">
                    <p class="text-lg text-pretty">
                        {{ $this->storeName() }} melayani kebutuhan cetak dan alat tulis sehari-hari:
                        fotocopy, print dokumen, scan, jilid, laminating, sampai kertas dan perlengkapan kantor.
                    </p>
                    <p class="text-pretty">
                        Kami sadar hal yang paling menyebalkan buat pelanggan adalah datang jauh-jauh
                        lalu barangnya habis, atau harganya ternyata di luar perkiraan. Karena itu seluruh
                        katalog beserta harga dan status ketersediaannya kami tampilkan terbuka di website ini,
                        dan diperbarui langsung dari sistem kasir toko.
                    </p>
                    <p class="text-pretty">
                        Semua pesanan dikerjakan dan dibayar langsung di toko. Website ini bukan toko online —
                        fungsinya membantu Anda memastikan dulu sebelum berangkat, atau bertanya lewat WhatsApp
                        kalau ada yang perlu dipastikan.
                    </p>
                </div>

                <div class="mt-10 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="text-3xl font-semibold tracking-tight tabular-nums">{{ $this->productCount() }}</div>
                        <div class="mt-1 text-sm text-zinc-500">Layanan &amp; produk</div>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="text-3xl font-semibold tracking-tight tabular-nums">{{ $this->categoryCount() }}</div>
                        <div class="mt-1 text-sm text-zinc-500">Kategori</div>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="text-3xl font-semibold tracking-tight">Rp</div>
                        <div class="mt-1 text-sm text-zinc-500">Harga terbuka, tanpa biaya kejutan</div>
                    </div>
                </div>
            </div>

            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="font-semibold">Yang kami kerjakan</h2>
                    <ul class="mt-4 space-y-3 text-sm text-zinc-700 dark:text-zinc-300">
                        @foreach (['Fotocopy hitam putih & warna', 'Print dokumen dari file', 'Scan dokumen', 'Jilid spiral & lakban', 'Laminating', 'Kertas & alat tulis'] as $layanan)
                            <li class="flex items-start gap-2.5">
                                <svg class="mt-0.5 size-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                {{ $layanan }}
                            </li>
                        @endforeach
                    </ul>

                    @if ($this->hours())
                        <div class="mt-6 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                            <div class="text-xs font-medium text-zinc-500">Jam operasional</div>
                            <div class="mt-1 text-sm font-medium">{{ $this->hours() }}</div>
                        </div>
                    @endif

                    <a
                        href="{{ route('katalog') }}"
                        wire:navigate
                        class="mt-6 flex items-center justify-center gap-2 rounded-xl bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                    >
                        Lihat katalog &amp; harga
                    </a>
                </div>
            </aside>
        </div>
    </section>
</div>
