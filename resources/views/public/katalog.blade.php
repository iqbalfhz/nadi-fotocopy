@php
    use App\Support\Rupiah;

    $waBase = $whatsapp ? 'https://wa.me/'.$whatsapp : null;
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $storeName }} — Katalog & Harga</title>
    <meta name="description" content="Katalog layanan dan produk {{ $storeName }}. Cek harga dan ketersediaan, lalu hubungi kami lewat WhatsApp.">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-zinc-900 dark:text-zinc-100">

    <header class="border-b border-zinc-200 dark:border-zinc-800">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-4">
            <div>
                <div class="text-lg font-semibold">{{ $storeName }}</div>
                @if ($storeHours)
                    <div class="text-sm text-zinc-500">{{ $storeHours }}</div>
                @endif
            </div>

            @if ($waBase)
                <a
                    href="{{ $waBase }}?text={{ rawurlencode('Halo '.$storeName.', saya mau bertanya.') }}"
                    target="_blank"
                    rel="noopener"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                >
                    Hubungi via WhatsApp
                </a>
            @endif
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8">
        <section class="mb-8">
            <h1 class="text-2xl font-semibold">Katalog & Harga</h1>
            <p class="mt-1 max-w-2xl text-zinc-600 dark:text-zinc-400">
                Cek harga dan ketersediaan dari HP. Pemesanan dan pembayaran dilakukan langsung di toko —
                hubungi kami lewat WhatsApp kalau mau bertanya dulu.
            </p>
            @if ($storeAddress)
                <p class="mt-3 text-sm text-zinc-500">📍 {{ $storeAddress }}</p>
            @endif
        </section>

        <form method="GET" class="mb-6 flex flex-wrap gap-2">
            <input
                type="search"
                name="q"
                value="{{ $search }}"
                placeholder="Cari layanan atau produk..."
                class="min-w-0 flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"
            >
            <select
                name="kategori"
                class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"
            >
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}" @selected($activeCategory === $category->slug)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
                Cari
            </button>
        </form>

        @forelse ($grouped as $categoryName => $products)
            <section class="mb-8">
                <h2 class="mb-3 text-lg font-semibold">{{ $categoryName }}</h2>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($products as $product)
                        <article class="flex flex-col justify-between rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                            <div>
                                <h3 class="font-medium">{{ $product->name }}</h3>
                                <div class="mt-1 text-lg font-semibold tabular-nums">
                                    {{ Rupiah::format($product->price) }}
                                    <span class="text-sm font-normal text-zinc-500">/ {{ $product->unit }}</span>
                                </div>

                                {{-- Hanya status, tidak pernah angka stok (§7.2). --}}
                                @if ($product->isAvailable())
                                    <span class="mt-2 inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        Tersedia
                                    </span>
                                @else
                                    <span class="mt-2 inline-block rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                        Habis
                                    </span>
                                @endif
                            </div>

                            @if ($waBase)
                                <a
                                    href="{{ $waBase }}?text={{ rawurlencode('Halo '.$storeName.', saya mau tanya soal "'.$product->name.'".') }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="mt-4 rounded-lg border border-emerald-600 px-3 py-2 text-center text-sm font-medium text-emerald-700 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/40"
                                >
                                    Tanya via WhatsApp
                                </a>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center text-zinc-500 dark:border-zinc-700">
                Tidak ada produk yang cocok dengan pencarian Anda.
            </div>
        @endforelse
    </main>

    <footer class="border-t border-zinc-200 dark:border-zinc-800">
        <div class="mx-auto max-w-5xl px-4 py-6 text-sm text-zinc-500">
            &copy; {{ now()->year }} {{ $storeName }}
        </div>
    </footer>

</body>
</html>
