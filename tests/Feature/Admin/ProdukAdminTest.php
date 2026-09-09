<?php

use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->category = Category::factory()->create();
});

test('kasir ditolak server saat membuka panel admin', function () {
    $kasir = User::factory()->kasir()->create();

    $this->actingAs($kasir)->get(route('admin.produk'))->assertForbidden();
    $this->actingAs($kasir)->get(route('admin.kategori'))->assertForbidden();
    $this->actingAs($kasir)->get(route('admin.pengaturan'))->assertForbidden();
});

test('tamu diarahkan ke login', function () {
    $this->get(route('admin.produk'))->assertRedirect(route('login'));
});

test('owner bisa membuka halaman produk', function () {
    $this->actingAs($this->owner)->get(route('admin.produk'))->assertOk();
});

test('owner bisa menambah produk baru', function () {
    Livewire::actingAs($this->owner)
        ->test('pages::admin.produk')
        ->call('create')
        ->set('name', 'Kertas HVS A4')
        ->set('categoryId', $this->category->id)
        ->set('price', 55000)
        ->set('unit', 'rim')
        ->set('type', 'produk')
        ->set('initialStock', 10)
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::query()->where('name', 'Kertas HVS A4')->firstOrFail();

    expect($product->slug)->toBe('kertas-hvs-a4')
        ->and($product->price)->toBe(55000)
        ->and($product->track_stock)->toBeTrue()
        ->and($product->stock)->toBe(10);
});

test('stok awal tercatat sebagai stok masuk bukan diisi diam-diam', function () {
    Livewire::actingAs($this->owner)
        ->test('pages::admin.produk')
        ->call('create')
        ->set('name', 'Amplop Coklat')
        ->set('categoryId', $this->category->id)
        ->set('price', 1500)
        ->set('initialStock', 40)
        ->call('save');

    $movement = StockMovement::query()->firstOrFail();

    expect($movement->type)->toBe(StockMovementType::StokMasuk)
        ->and($movement->qty_change)->toBe(40)
        ->and($movement->user_id)->toBe($this->owner->id);
});

test('jasa otomatis tidak melacak stok', function () {
    Livewire::actingAs($this->owner)
        ->test('pages::admin.produk')
        ->call('create')
        ->set('name', 'Laminating A4')
        ->set('categoryId', $this->category->id)
        ->set('price', 5000)
        ->set('type', 'jasa')
        ->call('save');

    $product = Product::query()->where('name', 'Laminating A4')->firstOrFail();

    expect($product->type)->toBe(ProductType::Jasa)
        ->and($product->track_stock)->toBeFalse();
});

test('validasi menolak produk tanpa nama', function () {
    Livewire::actingAs($this->owner)
        ->test('pages::admin.produk')
        ->call('create')
        ->set('name', '')
        ->set('categoryId', $this->category->id)
        ->call('save')
        ->assertHasErrors('name');

    expect(Product::count())->toBe(0);
});

test('owner bisa mengubah produk tanpa menyentuh stok', function () {
    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'name' => 'Nama Lama',
        'price' => 1000,
        'stock' => 42,
    ]);

    Livewire::actingAs($this->owner)
        ->test('pages::admin.produk')
        ->call('edit', $product->id)
        ->set('name', 'Nama Baru')
        ->set('price', 2000)
        ->call('save');

    $product->refresh();

    expect($product->name)->toBe('Nama Baru')
        ->and($product->price)->toBe(2000)
        ->and($product->stock)->toBe(42)
        ->and(StockMovement::count())->toBe(0);
});

test('penyesuaian stok mencatat selisih dan alasannya', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id, 'stock' => 20]);

    Livewire::actingAs($this->owner)
        ->test('pages::admin.produk')
        ->call('confirmAdjust', $product->id)
        ->set('newStock', 35)
        ->set('stockReason', 'stok masuk dari supplier')
        ->call('adjust');

    $movement = StockMovement::query()->firstOrFail();

    expect($product->fresh()->stock)->toBe(35)
        ->and($movement->type)->toBe(StockMovementType::Penyesuaian)
        ->and($movement->qty_change)->toBe(15)
        ->and($movement->note)->toBe('stok masuk dari supplier');
});

test('penyesuaian stok tanpa alasan ditolak', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id, 'stock' => 20]);

    Livewire::actingAs($this->owner)
        ->test('pages::admin.produk')
        ->call('confirmAdjust', $product->id)
        ->set('newStock', 5)
        ->set('stockReason', '  ')
        ->call('adjust');

    expect($product->fresh()->stock)->toBe(20)
        ->and(StockMovement::count())->toBe(0);
});

test('owner bisa menonaktifkan produk', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id]);

    Livewire::actingAs($this->owner)
        ->test('pages::admin.produk')
        ->call('toggleActive', $product->id);

    expect($product->fresh()->is_active)->toBeFalse();
});

test('slug tidak bentrok saat nama produk sama', function () {
    Product::factory()->create(['category_id' => $this->category->id, 'slug' => 'map-kertas']);

    Livewire::actingAs($this->owner)
        ->test('pages::admin.produk')
        ->call('create')
        ->set('name', 'Map Kertas')
        ->set('categoryId', $this->category->id)
        ->set('price', 2000)
        ->call('save')
        ->assertHasNoErrors();

    expect(Product::query()->where('slug', 'map-kertas-2')->exists())->toBeTrue();
});
