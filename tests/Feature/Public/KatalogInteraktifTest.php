<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Livewire\Livewire;

beforeEach(function () {
    Setting::put('store_name', "Nadi's Fotocopy");
    Setting::put('store_whatsapp', '08123456789');

    $this->fotocopy = Category::factory()->create(['name' => 'Layanan Fotocopy', 'slug' => 'layanan-fotocopy']);
    $this->atk = Category::factory()->create(['name' => 'Alat Tulis', 'slug' => 'alat-tulis']);

    Product::factory()->jasa()->create([
        'category_id' => $this->fotocopy->id,
        'name' => 'Fotocopy A4',
        'price' => 300,
    ]);

    Product::factory()->create([
        'category_id' => $this->atk->id,
        'name' => 'Pulpen Biru',
        'price' => 3500,
        'stock' => 20,
    ]);
});

test('pencarian live menyaring tanpa reload halaman', function () {
    Livewire::test('pages::public.katalog')
        ->assertSeeText('Fotocopy A4')
        ->assertSeeText('Pulpen Biru')
        ->set('search', 'Pulpen')
        ->assertSeeText('Pulpen Biru')
        ->assertDontSeeText('Fotocopy A4');
});

test('klik chip kategori menyaring produk', function () {
    Livewire::test('pages::public.katalog')
        ->call('selectCategory', 'alat-tulis')
        ->assertSet('category', 'alat-tulis')
        ->assertSeeText('Pulpen Biru')
        ->assertDontSeeText('Fotocopy A4');
});

test('klik chip yang sama kedua kali melepas filter', function () {
    Livewire::test('pages::public.katalog')
        ->call('selectCategory', 'alat-tulis')
        ->assertSet('category', 'alat-tulis')
        ->call('selectCategory', 'alat-tulis')
        ->assertSet('category', '')
        ->assertSeeText('Fotocopy A4');
});

test('hapus filter mengembalikan semua produk', function () {
    Livewire::test('pages::public.katalog')
        ->set('search', 'Pulpen')
        ->call('selectCategory', 'alat-tulis')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('category', '')
        ->assertSeeText('Fotocopy A4')
        ->assertSeeText('Pulpen Biru');
});

test('jumlah hasil ditampilkan', function () {
    Livewire::test('pages::public.katalog')
        ->assertSeeText('2')
        ->set('search', 'Pulpen')
        ->assertSeeText('1');
});

test('menampilkan pesan kosong saat tidak ada yang cocok', function () {
    Livewire::test('pages::public.katalog')
        ->set('search', 'barang yang tidak ada')
        ->assertSeeText('Tidak ada yang cocok');
});

test('query string terisi dari state komponen', function () {
    Livewire::withQueryParams(['q' => 'Pulpen'])
        ->test('pages::public.katalog')
        ->assertSet('search', 'Pulpen')
        ->assertSeeText('Pulpen Biru')
        ->assertDontSeeText('Fotocopy A4');
});

test('kategori tanpa produk aktif tidak muncul sebagai chip', function () {
    $kosong = Category::factory()->create(['name' => 'Kategori Kosong']);

    Livewire::test('pages::public.katalog')
        ->assertSeeText('Alat Tulis')
        ->assertDontSeeText($kosong->name);
});

test('link whatsapp per produk menyebut nama produknya', function () {
    Livewire::test('pages::public.katalog')
        ->assertSee('https://wa.me/628123456789', escape: false)
        ->assertSee(rawurlencode('saya mau tanya soal "Pulpen Biru"'), escape: false);
});

test('tetap tidak membocorkan angka stok setelah redesign', function () {
    Product::factory()->create([
        'category_id' => $this->atk->id,
        'name' => 'Buku Tulis',
        'stock' => 137,
    ]);

    Livewire::test('pages::public.katalog')
        ->assertSeeText('Buku Tulis')
        ->assertDontSee('137');
});
