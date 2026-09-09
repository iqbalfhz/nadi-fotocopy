@php
    use App\Support\StoreProfile;

    $storeName = StoreProfile::name();
    $waLink = StoreProfile::whatsappLink('Halo '.$storeName.', saya mau bertanya.');

    $nav = [
        ['route' => 'home', 'label' => 'Beranda'],
        ['route' => 'katalog', 'label' => 'Katalog & Harga'],
        ['route' => 'tentang', 'label' => 'Tentang'],
        ['route' => 'kontak', 'label' => 'Kontak'],
    ];
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
    <head>
        @include('partials.head')

        @isset($description)
            <meta name="description" content="{{ $description }}">
        @endisset
    </head>
    <body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">

        <header
            x-data="{ open: false }"
            class="sticky top-0 z-40 border-b border-zinc-200/80 bg-white/80 backdrop-blur-lg dark:border-zinc-800/80 dark:bg-zinc-950/80"
        >
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
                <a href="{{ route('home') }}" wire:navigate class="flex min-w-0 items-center gap-2.5">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm shadow-emerald-600/30">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75V4.5a1.5 1.5 0 0 0-1.5-1.5H6.75a1.5 1.5 0 0 0-1.5 1.5v2.25M6.75 17.25V19.5a1.5 1.5 0 0 0 1.5 1.5h7.5a1.5 1.5 0 0 0 1.5-1.5v-2.25M4.5 6.75h15a1.5 1.5 0 0 1 1.5 1.5v6a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 14.25v-6a1.5 1.5 0 0 1 1.5-1.5Z" />
                        </svg>
                    </span>
                    <span class="truncate text-base font-semibold tracking-tight">{{ $storeName }}</span>
                </a>

                <nav class="ms-6 hidden items-center gap-1 md:flex">
                    @foreach ($nav as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            wire:navigate
                            @class([
                                'rounded-lg px-3 py-2 text-sm font-medium transition',
                                'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' => request()->routeIs($item['route']),
                                'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' => ! request()->routeIs($item['route']),
                            ])
                        >
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="ms-auto flex items-center gap-1.5">
                    <button
                        type="button"
                        x-data
                        x-on:click="$flux.appearance = $flux.appearance === 'dark' ? 'light' : 'dark'"
                        class="flex size-9 items-center justify-center rounded-xl text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-white"
                        aria-label="Ganti tema terang atau gelap"
                    >
                        <svg class="size-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                        </svg>
                        <svg class="hidden size-5 dark:block" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                        </svg>
                    </button>

                    @if ($waLink)
                        <a
                            href="{{ $waLink }}"
                            target="_blank"
                            rel="noopener"
                            class="hidden items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm shadow-emerald-600/25 transition hover:bg-emerald-700 sm:inline-flex"
                        >
                            <x-wa-icon class="size-4" />
                            WhatsApp
                        </a>
                    @endif

                    <button
                        type="button"
                        x-on:click="open = ! open"
                        class="flex size-9 items-center justify-center rounded-xl text-zinc-500 transition hover:bg-zinc-100 md:hidden dark:hover:bg-zinc-800"
                        aria-label="Buka menu"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Menu mobile --}}
            <nav x-show="open" x-collapse x-cloak class="border-t border-zinc-200 md:hidden dark:border-zinc-800">
                <div class="mx-auto max-w-6xl space-y-1 px-4 py-3">
                    @foreach ($nav as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            wire:navigate
                            x-on:click="open = false"
                            @class([
                                'block rounded-lg px-3 py-2 text-sm font-medium transition',
                                'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' => request()->routeIs($item['route']),
                                'text-zinc-600 dark:text-zinc-400' => ! request()->routeIs($item['route']),
                            ])
                        >
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </nav>
        </header>

        {{ $slot }}

        <footer class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900/40">
            <div class="mx-auto max-w-6xl px-4 py-10">
                <div class="flex flex-wrap justify-between gap-8">
                    <div class="max-w-sm">
                        <div class="text-base font-semibold tracking-tight">{{ $storeName }}</div>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                            Fotocopy, print, jilid, dan alat tulis. Cek harga dari HP, pemesanan dilakukan langsung di toko.
                        </p>
                    </div>

                    <div>
                        <div class="text-sm font-medium">Halaman</div>
                        <ul class="mt-3 space-y-2 text-sm">
                            @foreach ($nav as $item)
                                <li>
                                    <a href="{{ route($item['route']) }}" wire:navigate class="text-zinc-600 transition hover:text-emerald-700 dark:text-zinc-400 dark:hover:text-emerald-400">
                                        {{ $item['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div>
                        <div class="text-sm font-medium">Kontak</div>
                        <ul class="mt-3 space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                            @if (StoreProfile::hours())
                                <li>{{ StoreProfile::hours() }}</li>
                            @endif
                            @if (StoreProfile::address())
                                <li class="max-w-xs">{{ StoreProfile::address() }}</li>
                            @endif
                            @if ($waLink)
                                <li>
                                    <a href="{{ $waLink }}" target="_blank" rel="noopener" class="text-emerald-700 hover:underline dark:text-emerald-400">
                                        +{{ StoreProfile::whatsappNumber() }}
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                <div class="mt-8 border-t border-zinc-200 pt-6 text-sm text-zinc-500 dark:border-zinc-800">
                    &copy; {{ now()->year }} {{ $storeName }}
                </div>
            </div>
        </footer>

        @if ($waLink)
            <a
                href="{{ $waLink }}"
                target="_blank"
                rel="noopener"
                class="fixed end-4 bottom-4 z-40 flex size-14 items-center justify-center rounded-full bg-emerald-600 text-white shadow-lg shadow-emerald-600/30 transition hover:bg-emerald-700 sm:hidden"
                aria-label="Hubungi via WhatsApp"
            >
                <x-wa-icon class="size-7" />
            </a>
        @endif

        @fluxScripts
    </body>
</html>
