<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Support\StoreProfile;

beforeEach(function () {
    StoreProfile::flush();

    Setting::put('store_name', "Nadi's Fotocopy");
    Setting::put('store_address', 'Jl. Contoh No. 1');
    Setting::put('store_hours', 'Senin–Sabtu 08.00–20.00');
    Setting::put('store_whatsapp', '08123456789');

    $this->category = Category::factory()->create(['name' => 'Layanan Fotocopy', 'slug' => 'layanan-fotocopy']);
});

test('keempat halaman publik bisa dibuka tanpa login', function (string $route) {
    $this->get(route($route))->assertOk();
})->with(['home', 'katalog', 'tentang', 'kontak']);

test('setiap halaman publik memuat navigasi lengkap', function (string $route) {
    $response = $this->get(route($route));

    $response->assertSeeText('Beranda');
    $response->assertSeeText('Katalog & Harga');
    $response->assertSeeText('Tentang');
    $response->assertSeeText('Kontak');
})->with(['home', 'katalog', 'tentang', 'kontak']);

test('beranda menampilkan profil toko', function () {
    $this->get(route('home'))
        ->assertSeeText("Nadi's Fotocopy")
        ->assertSeeText('Senin–Sabtu 08.00–20.00')
        ->assertSeeText('Jl. Contoh No. 1');
});

test('beranda menampilkan kategori yang punya produk aktif', function () {
    Product::factory()->create(['category_id' => $this->category->id]);

    $this->get(route('home'))
        ->assertSeeText('Layanan Fotocopy')
        ->assertSee(route('katalog', ['kategori' => 'layanan-fotocopy']), escape: false);
});

test('beranda menyembunyikan kategori tanpa produk aktif', function () {
    $kosong = Category::factory()->create(['name' => 'Kategori Kosong']);

    $this->get(route('home'))->assertDontSeeText($kosong->name);
});

test('beranda menampilkan contoh harga layanan termurah lebih dulu', function () {
    Product::factory()->jasa()->create([
        'category_id' => $this->category->id,
        'name' => 'Fotocopy Murah',
        'price' => 300,
    ]);
    Product::factory()->jasa()->create([
        'category_id' => $this->category->id,
        'name' => 'Jilid Mahal',
        'price' => 15000,
    ]);

    $response = $this->get(route('home'));

    $response->assertSeeText('Fotocopy Murah');
    $response->assertSeeInOrder(['Fotocopy Murah', 'Jilid Mahal']);
});

test('beranda tidak membocorkan angka stok', function () {
    Product::factory()->create([
        'category_id' => $this->category->id,
        'name' => 'Kertas HVS',
        'stock' => 137,
    ]);

    $this->get(route('home'))->assertDontSee('137');
});

test('tentang menampilkan jumlah layanan aktif', function () {
    Product::factory()->count(3)->create(['category_id' => $this->category->id]);
    Product::factory()->inactive()->create(['category_id' => $this->category->id]);

    $this->get(route('tentang'))
        ->assertOk()
        ->assertSeeText('Layanan & produk');
});

test('kontak menampilkan alamat, jam, dan whatsapp', function () {
    $this->get(route('kontak'))
        ->assertSeeText('Jl. Contoh No. 1')
        ->assertSeeText('Senin–Sabtu 08.00–20.00')
        ->assertSeeText('+628123456789');
});

test('kontak menyediakan tautan google maps dari alamat toko', function () {
    $this->get(route('kontak'))
        ->assertSee('https://www.google.com/maps/search/', escape: false)
        ->assertSee(rawurlencode('Jl. Contoh No. 1'), escape: false);
});

test('kontak menegaskan ini bukan toko online', function () {
    $this->get(route('kontak'))->assertSeeText('bukan toko online');
});

test('halaman publik tidak membocorkan data laporan penjualan', function (string $route) {
    $this->get(route($route))
        ->assertDontSeeText('Omzet')
        ->assertDontSeeText('TRX-');
})->with(['home', 'katalog', 'tentang', 'kontak']);

test('profil toko terbaca ulang setelah pengaturan diubah', function () {
    $this->get(route('kontak'))->assertSeeText('Jl. Contoh No. 1');

    Setting::put('store_address', 'Jl. Baru No. 99');

    $this->get(route('kontak'))->assertSeeText('Jl. Baru No. 99');
});
