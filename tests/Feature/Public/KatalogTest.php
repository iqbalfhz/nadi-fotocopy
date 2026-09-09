<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;

beforeEach(function () {
    Setting::put('store_name', "Nadi's Fotocopy");
    Setting::put('store_whatsapp', '08123456789');
    Setting::put('store_hours', 'Senin–Sabtu 08.00–20.00');
    Setting::put('store_address', 'Jl. Contoh No. 1');

    $this->category = Category::factory()->create(['name' => 'Alat Tulis']);
});

test('katalog bisa dibuka tanpa login', function () {
    $this->get(route('katalog'))->assertOk();
});

test('katalog menautkan ke halaman publik lainnya lewat navigasi', function () {
    $this->get(route('katalog'))
        ->assertSee(route('home'), escape: false)
        ->assertSee(route('tentang'), escape: false)
        ->assertSee(route('kontak'), escape: false);
});

test('menampilkan produk aktif beserta harganya', function () {
    Product::factory()->create([
        'category_id' => $this->category->id,
        'name' => 'Pulpen Standard',
        'price' => 3500,
        'unit' => 'pcs',
        'stock' => 10,
    ]);

    $this->get(route('katalog'))
        ->assertSeeText('Pulpen Standard')
        ->assertSeeText('Rp 3.500')
        ->assertSeeText('Tersedia');
});

test('tidak pernah membocorkan angka stok ke halaman publik', function () {
    Product::factory()->create([
        'category_id' => $this->category->id,
        'name' => 'Buku Tulis',
        'price' => 5000,
        'stock' => 137,
    ]);

    $response = $this->get(route('katalog'));

    $response->assertSeeText('Tersedia');
    $response->assertDontSee('137');
});

test('produk habis ditandai habis', function () {
    Product::factory()->outOfStock()->create([
        'category_id' => $this->category->id,
        'name' => 'Spidol Hitam',
    ]);

    $this->get(route('katalog'))
        ->assertSeeText('Spidol Hitam')
        ->assertSeeText('Habis');
});

test('jasa selalu tampil tersedia walau stok nol', function () {
    Product::factory()->jasa()->create([
        'category_id' => $this->category->id,
        'name' => 'Fotocopy A4',
        'price' => 300,
    ]);

    $response = $this->get(route('katalog'));

    $response->assertSeeText('Fotocopy A4');
    $response->assertSeeText('Tersedia');
    $response->assertDontSeeText('Habis');
});

test('produk nonaktif tidak muncul di katalog', function () {
    Product::factory()->create(['category_id' => $this->category->id, 'name' => 'Produk Tampil']);
    Product::factory()->inactive()->create(['category_id' => $this->category->id, 'name' => 'Produk Sembunyi']);

    $this->get(route('katalog'))
        ->assertSeeText('Produk Tampil')
        ->assertDontSeeText('Produk Sembunyi');
});

test('tombol whatsapp memakai nomor toko yang sudah dinormalkan', function () {
    Product::factory()->create(['category_id' => $this->category->id, 'name' => 'Map Kertas']);

    // 08123456789 harus jadi 628123456789.
    $this->get(route('katalog'))->assertSee('https://wa.me/628123456789', escape: false);
});

test('pencarian menyaring produk', function () {
    Product::factory()->create(['category_id' => $this->category->id, 'name' => 'Kertas HVS']);
    Product::factory()->create(['category_id' => $this->category->id, 'name' => 'Pensil 2B']);

    $this->get(route('katalog', ['q' => 'Kertas']))
        ->assertSeeText('Kertas HVS')
        ->assertDontSeeText('Pensil 2B');
});

test('filter kategori menyaring produk', function () {
    $lain = Category::factory()->create(['name' => 'Kertas', 'slug' => 'kertas']);

    Product::factory()->create(['category_id' => $this->category->id, 'name' => 'Pulpen Biru']);
    Product::factory()->create(['category_id' => $lain->id, 'name' => 'HVS A4']);

    $this->get(route('katalog', ['kategori' => 'kertas']))
        ->assertSeeText('HVS A4')
        ->assertDontSeeText('Pulpen Biru');
});

test('katalog tidak membocorkan data laporan penjualan', function () {
    Product::factory()->create(['category_id' => $this->category->id]);

    $this->get(route('katalog'))
        ->assertDontSeeText('Omzet')
        ->assertDontSeeText('TRX-');
});
