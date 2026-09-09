<?php

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->owner = User::factory()->create();
});

test('tamu tidak bisa membuka layar kasir', function () {
    $this->get(route('pos.kasir'))->assertRedirect(route('login'));
});

test('layar kasir bisa dibuka user yang login', function () {
    $this->actingAs($this->owner)->get(route('pos.kasir'))->assertOk();
});

test('menambah produk ke keranjang menghitung total', function () {
    $product = Product::factory()->create(['price' => 2500, 'stock' => 10]);

    Livewire::actingAs($this->owner)
        ->test('pages::pos.kasir')
        ->call('addToCart', $product->id)
        ->call('addToCart', $product->id)
        ->assertSet('cart', [$product->id => 2])
        ->assertSeeText('Rp 5.000');
});

test('tidak bisa menambah melebihi stok', function () {
    $product = Product::factory()->create(['price' => 1000, 'stock' => 1]);

    Livewire::actingAs($this->owner)
        ->test('pages::pos.kasir')
        ->call('addToCart', $product->id)
        ->call('addToCart', $product->id)
        ->assertSet('cart', [$product->id => 1]);
});

test('checkout menyimpan transaksi dan mengarahkan ke struk', function () {
    $product = Product::factory()->create(['price' => 5000, 'stock' => 10]);

    Livewire::actingAs($this->owner)
        ->test('pages::pos.kasir')
        ->call('addToCart', $product->id)
        ->set('paymentMethod', 'cash')
        ->set('paidAmount', 10000)
        ->call('checkout')
        ->assertRedirect();

    $transaction = Transaction::query()->firstOrFail();

    expect($transaction->total)->toBe(5000)
        ->and($transaction->change_amount)->toBe(5000)
        ->and($transaction->payment_method)->toBe(PaymentMethod::Cash)
        ->and($product->fresh()->stock)->toBe(9);
});

test('checkout dengan uang kurang tidak menyimpan transaksi', function () {
    $product = Product::factory()->create(['price' => 5000, 'stock' => 10]);

    Livewire::actingAs($this->owner)
        ->test('pages::pos.kasir')
        ->call('addToCart', $product->id)
        ->set('paidAmount', 1000)
        ->call('checkout');

    expect(Transaction::count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(10);
});

test('tombol uang pas mengisi sebesar total', function () {
    $product = Product::factory()->create(['price' => 7500, 'stock' => 10]);

    Livewire::actingAs($this->owner)
        ->test('pages::pos.kasir')
        ->call('addToCart', $product->id)
        ->call('payExact')
        ->assertSet('paidAmount', 7500);
});

test('keranjang bisa dikosongkan', function () {
    $product = Product::factory()->create(['price' => 1000, 'stock' => 10]);

    Livewire::actingAs($this->owner)
        ->test('pages::pos.kasir')
        ->call('addToCart', $product->id)
        ->call('resetCart')
        ->assertSet('cart', [])
        ->assertSet('paidAmount', 0);
});

test('produk nonaktif tidak muncul di katalog', function () {
    $aktif = Product::factory()->create(['name' => 'Kertas Aktif', 'stock' => 5]);
    $nonaktif = Product::factory()->inactive()->create(['name' => 'Kertas Nonaktif', 'stock' => 5]);

    Livewire::actingAs($this->owner)
        ->test('pages::pos.kasir')
        ->assertSeeText($aktif->name)
        ->assertDontSeeText($nonaktif->name);
});

test('riwayat menampilkan transaksi dan owner bisa membatalkan', function () {
    $product = Product::factory()->create(['price' => 1000, 'stock' => 10]);

    Livewire::actingAs($this->owner)
        ->test('pages::pos.kasir')
        ->call('addToCart', $product->id)
        ->set('paymentMethod', 'qris')
        ->call('checkout');

    $transaction = Transaction::query()->firstOrFail();
    expect($product->fresh()->stock)->toBe(9);

    Livewire::actingAs($this->owner)
        ->test('pages::pos.riwayat')
        ->assertSeeText($transaction->transaction_number)
        ->call('confirmVoid', $transaction->id)
        ->set('voidReason', 'salah input')
        ->call('void');

    expect($transaction->fresh()->status)->toBe(TransactionStatus::Dibatalkan)
        ->and($product->fresh()->stock)->toBe(10);
});

test('halaman struk bisa dibuka dan memuat nomor transaksi', function () {
    $transaction = Transaction::factory()->create(['user_id' => $this->owner->id]);

    $this->actingAs($this->owner)
        ->get(route('pos.struk', $transaction))
        ->assertOk()
        ->assertSeeText($transaction->transaction_number);
});

test('tamu tidak bisa membuka struk', function () {
    $transaction = Transaction::factory()->create(['user_id' => $this->owner->id]);

    $this->get(route('pos.struk', $transaction))->assertRedirect(route('login'));
});
