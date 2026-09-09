<?php

use App\Support\StoreProfile;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Kontak')] class extends Component {
    public function storeName(): string
    {
        return StoreProfile::name();
    }

    public function address(): ?string
    {
        return StoreProfile::address();
    }

    public function hours(): ?string
    {
        return StoreProfile::hours();
    }

    public function whatsappNumber(): ?string
    {
        return StoreProfile::whatsappNumber();
    }

    public function mapsLink(): ?string
    {
        return StoreProfile::mapsLink();
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
            <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Kontak &amp; Lokasi</h1>
            <p class="mt-2 max-w-xl text-pretty text-zinc-600 dark:text-zinc-400">
                Datang langsung ke toko, atau tanya dulu lewat WhatsApp sebelum berangkat.
            </p>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-14">
        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Kartu kontak --}}
            <div class="space-y-4">
                <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-start gap-4">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-sm font-medium text-zinc-500">Alamat toko</h2>
                            <p class="mt-1 font-medium text-pretty">{{ $this->address() ?? 'Belum diisi' }}</p>
                            @if ($this->mapsLink())
                                <a
                                    href="{{ $this->mapsLink() }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400"
                                >
                                    Buka di Google Maps
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-start gap-4">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-medium text-zinc-500">Jam operasional</h2>
                            <p class="mt-1 font-medium">{{ $this->hours() ?? 'Belum diisi' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-start gap-4">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                            <x-wa-icon class="size-6" />
                        </span>
                        <div>
                            <h2 class="text-sm font-medium text-zinc-500">WhatsApp</h2>
                            @if ($this->waLink())
                                <p class="mt-1 font-medium">+{{ $this->whatsappNumber() }}</p>
                                <a
                                    href="{{ $this->waLink() }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="mt-3 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700"
                                >
                                    <x-wa-icon class="size-4" />
                                    Mulai chat
                                </a>
                            @else
                                <p class="mt-1 font-medium">Belum diisi</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Catatan pemesanan --}}
            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-800 dark:bg-zinc-900/60">
                <h2 class="text-lg font-semibold tracking-tight">Cara memesan</h2>

                <ol class="mt-6 space-y-6">
                    <li class="flex gap-4">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">1</span>
                        <div>
                            <h3 class="font-medium">Cek katalog</h3>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                Lihat harga dan ketersediaan di halaman
                                <a href="{{ route('katalog') }}" wire:navigate class="font-medium text-emerald-700 hover:underline dark:text-emerald-400">Katalog &amp; Harga</a>.
                            </p>
                        </div>
                    </li>
                    <li class="flex gap-4">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">2</span>
                        <div>
                            <h3 class="font-medium">Tanya lewat WhatsApp <span class="font-normal text-zinc-500">(opsional)</span></h3>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                Kalau perlu memastikan stok, jumlah, atau estimasi biaya, chat kami dulu.
                            </p>
                        </div>
                    </li>
                    <li class="flex gap-4">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">3</span>
                        <div>
                            <h3 class="font-medium">Datang ke toko</h3>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                Pesanan dikerjakan di tempat. Pembayaran bisa tunai, QRIS, atau kartu debit.
                            </p>
                        </div>
                    </li>
                </ol>

                <div class="mt-8 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
                    Website ini bukan toko online — tidak ada keranjang belanja maupun pembayaran daring.
                    Semua transaksi dilakukan langsung di toko.
                </div>
            </div>
        </div>
    </section>
</div>
