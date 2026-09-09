<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->owner = User::factory()->create();
});

test('owner bisa menambah kategori', function () {
    Livewire::actingAs($this->owner)
        ->test('pages::admin.kategori')
        ->call('create')
        ->set('name', 'Layanan Fotocopy')
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::query()->firstOrFail();

    expect($category->name)->toBe('Layanan Fotocopy')
        ->and($category->slug)->toBe('layanan-fotocopy');
});

test('kategori bisa diubah', function () {
    $category = Category::factory()->create(['name' => 'Lama']);

    Livewire::actingAs($this->owner)
        ->test('pages::admin.kategori')
        ->call('edit', $category->id)
        ->set('name', 'Baru')
        ->call('save');

    expect($category->fresh()->name)->toBe('Baru');
});

test('kategori kosong bisa dihapus', function () {
    $category = Category::factory()->create();

    Livewire::actingAs($this->owner)
        ->test('pages::admin.kategori')
        ->call('delete', $category->id);

    expect(Category::count())->toBe(0);
});

test('kategori yang masih punya produk tidak bisa dihapus', function () {
    $category = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id]);

    Livewire::actingAs($this->owner)
        ->test('pages::admin.kategori')
        ->call('delete', $category->id);

    expect(Category::count())->toBe(1);
});

test('kasir ditolak membuka kategori', function () {
    $kasir = User::factory()->kasir()->create();

    $this->actingAs($kasir)->get(route('admin.kategori'))->assertForbidden();
});

test('owner bisa menyimpan pengaturan toko', function () {
    Livewire::actingAs($this->owner)
        ->test('pages::admin.pengaturan')
        ->set('storeName', "Nadi's Fotocopy")
        ->set('storeAddress', 'Jl. Mawar No. 7')
        ->set('storeHours', 'Setiap hari 08.00–21.00')
        ->set('storeWhatsapp', '628111222333')
        ->set('receiptFooter', 'Terima kasih!')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('store_name'))->toBe("Nadi's Fotocopy")
        ->and(Setting::get('store_whatsapp'))->toBe('628111222333');
});

test('nama toko wajib diisi', function () {
    Livewire::actingAs($this->owner)
        ->test('pages::admin.pengaturan')
        ->set('storeName', '')
        ->call('save')
        ->assertHasErrors('storeName');
});

test('pengaturan toko muncul di struk', function () {
    Setting::put('store_name', 'Toko Uji Coba');
    Setting::put('receipt_footer', 'Sampai jumpa lagi');

    $transaction = Transaction::factory()->create(['user_id' => $this->owner->id]);

    $this->actingAs($this->owner)
        ->get(route('pos.struk', $transaction))
        ->assertOk()
        ->assertSeeText('Toko Uji Coba')
        ->assertSeeText('Sampai jumpa lagi');
});
