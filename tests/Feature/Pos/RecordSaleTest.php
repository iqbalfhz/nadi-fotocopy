<?php

use App\Actions\Pos\CartLine;
use App\Actions\Pos\RecordSale;
use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\StockMovementType;
use App\Enums\TransactionStatus;
use App\Exceptions\PosException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->cashier = User::factory()->create();
    $this->sale = app(RecordSale::class);
});

test('menyimpan transaksi dan memotong stok', function () {
    $product = Product::factory()->create(['price' => 5000, 'stock' => 100]);

    $transaction = $this->sale->handle(
        cashier: $this->cashier,
        lines: [new CartLine($product->id, 3)],
        paymentMethod: PaymentMethod::Cash,
        paidAmount: 20000,
    );

    expect($transaction->subtotal)->toBe(15000)
        ->and($transaction->total)->toBe(15000)
        ->and($transaction->paid_amount)->toBe(20000)
        ->and($transaction->change_amount)->toBe(5000)
        ->and($transaction->status)->toBe(TransactionStatus::Selesai)
        ->and($product->fresh()->stock)->toBe(97);
});

test('mencatat pergerakan stok bertipe penjualan', function () {
    $product = Product::factory()->create(['price' => 1000, 'stock' => 50]);

    $transaction = $this->sale->handle(
        cashier: $this->cashier,
        lines: [new CartLine($product->id, 4)],
        paymentMethod: PaymentMethod::Cash,
        paidAmount: 4000,
    );

    $movement = StockMovement::query()->firstOrFail();

    expect($movement->type)->toBe(StockMovementType::Penjualan)
        ->and($movement->qty_change)->toBe(-4)
        ->and($movement->product_id)->toBe($product->id)
        ->and($movement->user_id)->toBe($this->cashier->id)
        ->and($movement->reference_id)->toBe($transaction->id);
});

test('harga item adalah snapshot dan tidak ikut berubah saat harga produk diubah', function () {
    $product = Product::factory()->create(['price' => 2000, 'stock' => 10]);

    $transaction = $this->sale->handle(
        cashier: $this->cashier,
        lines: [new CartLine($product->id, 2)],
        paymentMethod: PaymentMethod::Cash,
        paidAmount: 4000,
    );

    $product->update(['price' => 9999]);

    $item = $transaction->items()->firstOrFail();

    expect($item->price)->toBe(2000)
        ->and($item->subtotal)->toBe(4000)
        ->and($item->product_name)->toBe($product->name);
});

test('menolak transaksi kalau stok tidak cukup dan tidak menyimpan apa pun', function () {
    $product = Product::factory()->create(['price' => 1000, 'stock' => 2]);

    expect(fn () => $this->sale->handle(
        cashier: $this->cashier,
        lines: [new CartLine($product->id, 5)],
        paymentMethod: PaymentMethod::Cash,
        paidAmount: 5000,
    ))->toThrow(PosException::class);

    expect(Transaction::count())->toBe(0)
        ->and(StockMovement::count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(2);
});

test('jasa tidak memotong stok dan tidak membuat pergerakan stok', function () {
    $jasa = Product::factory()->jasa()->create(['price' => 500]);

    $transaction = $this->sale->handle(
        cashier: $this->cashier,
        lines: [new CartLine($jasa->id, 20)],
        paymentMethod: PaymentMethod::Cash,
        paidAmount: 10000,
    );

    expect($transaction->total)->toBe(10000)
        ->and($jasa->fresh()->stock)->toBe(0)
        ->and(StockMovement::count())->toBe(0);
});

test('menolak produk yang nonaktif', function () {
    $product = Product::factory()->inactive()->create(['price' => 1000, 'stock' => 10]);

    expect(fn () => $this->sale->handle(
        cashier: $this->cashier,
        lines: [new CartLine($product->id, 1)],
        paymentMethod: PaymentMethod::Cash,
        paidAmount: 1000,
    ))->toThrow(PosException::class);
});

test('menolak keranjang kosong', function () {
    expect(fn () => $this->sale->handle(
        cashier: $this->cashier,
        lines: [],
        paymentMethod: PaymentMethod::Cash,
    ))->toThrow(PosException::class);
});

test('menghitung diskon nominal', function () {
    $product = Product::factory()->create(['price' => 10000, 'stock' => 10]);

    $transaction = $this->sale->handle(
        cashier: $this->cashier,
        lines: [new CartLine($product->id, 2)],
        paymentMethod: PaymentMethod::Cash,
        discountType: DiscountType::Nominal,
        discountValue: 5000,
        paidAmount: 20000,
    );

    expect($transaction->subtotal)->toBe(20000)
        ->and($transaction->discount_amount)->toBe(5000)
        ->and($transaction->total)->toBe(15000)
        ->and($transaction->change_amount)->toBe(5000);
});

test('menghitung diskon persen', function () {
    $product = Product::factory()->create(['price' => 10000, 'stock' => 10]);

    $transaction = $this->sale->handle(
        cashier: $this->cashier,
        lines: [new CartLine($product->id, 1)],
        paymentMethod: PaymentMethod::Cash,
        discountType: DiscountType::Persen,
        discountValue: 10,
        paidAmount: 10000,
    );

    expect($transaction->discount_amount)->toBe(1000)
        ->and($transaction->total)->toBe(9000);
});

test('diskon tidak pernah melebihi subtotal', function () {
    $product = Product::factory()->create(['price' => 1000, 'stock' => 10]);

    $transaction = $this->sale->handle(
        cashier: $this->cashier,
        lines: [new CartLine($product->id, 1)],
        paymentMethod: PaymentMethod::Cash,
        discountType: DiscountType::Nominal,
        discountValue: 999999,
        paidAmount: 0,
    );

    expect($transaction->discount_amount)->toBe(1000)
        ->and($transaction->total)->toBe(0);
});

test('menolak uang cash yang kurang dari total', function () {
    $product = Product::factory()->create(['price' => 10000, 'stock' => 10]);

    expect(fn () => $this->sale->handle(
        cashier: $this->cashier,
        lines: [new CartLine($product->id, 1)],
        paymentMethod: PaymentMethod::Cash,
        paidAmount: 5000,
    ))->toThrow(PosException::class);

    expect(Transaction::count())->toBe(0);
});

test('qris dan debit otomatis lunas tanpa kembalian', function () {
    $product = Product::factory()->create(['price' => 7500, 'stock' => 10]);

    $transaction = $this->sale->handle(
        cashier: $this->cashier,
        lines: [new CartLine($product->id, 2)],
        paymentMethod: PaymentMethod::Qris,
    );

    expect($transaction->total)->toBe(15000)
        ->and($transaction->paid_amount)->toBe(15000)
        ->and($transaction->change_amount)->toBe(0);
});

test('nomor transaksi berformat benar dan urut per hari', function () {
    $product = Product::factory()->create(['price' => 1000, 'stock' => 100]);

    $first = $this->sale->handle(
        cashier: $this->cashier,
        lines: [new CartLine($product->id, 1)],
        paymentMethod: PaymentMethod::Qris,
    );

    $second = $this->sale->handle(
        cashier: $this->cashier,
        lines: [new CartLine($product->id, 1)],
        paymentMethod: PaymentMethod::Qris,
    );

    $today = now()->format('Ymd');

    expect($first->transaction_number)->toBe("TRX-{$today}-0001")
        ->and($second->transaction_number)->toBe("TRX-{$today}-0002");
});

test('mencatat kasir yang melakukan transaksi', function () {
    $product = Product::factory()->create(['price' => 1000, 'stock' => 10]);

    $transaction = $this->sale->handle(
        cashier: $this->cashier,
        lines: [new CartLine($product->id, 1)],
        paymentMethod: PaymentMethod::Qris,
    );

    expect($transaction->user_id)->toBe($this->cashier->id);
});
