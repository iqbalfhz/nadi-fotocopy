<?php

use App\Actions\Pos\CartLine;
use App\Actions\Pos\RecordSale;
use App\Actions\Pos\VoidTransaction;
use App\Enums\PaymentMethod;
use App\Enums\StockMovementType;
use App\Enums\TransactionStatus;
use App\Exceptions\PosException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->sale = app(RecordSale::class);
    $this->void = app(VoidTransaction::class);
});

function buatTransaksi(User $owner, Product $product, int $qty = 3): Transaction
{
    return app(RecordSale::class)->handle(
        cashier: $owner,
        lines: [new CartLine($product->id, $qty)],
        paymentMethod: PaymentMethod::Qris,
    );
}

test('void mengembalikan stok produk', function () {
    $product = Product::factory()->create(['price' => 1000, 'stock' => 100]);
    $transaction = buatTransaksi($this->owner, $product, 3);

    expect($product->fresh()->stock)->toBe(97);

    $this->void->handle($transaction, $this->owner, 'salah input');

    expect($product->fresh()->stock)->toBe(100);
});

test('void mencatat pergerakan stok bertipe pembatalan', function () {
    $product = Product::factory()->create(['price' => 1000, 'stock' => 50]);
    $transaction = buatTransaksi($this->owner, $product, 5);

    $this->void->handle($transaction, $this->owner, 'pelanggan batal');

    $movement = StockMovement::query()
        ->where('type', StockMovementType::Pembatalan)
        ->firstOrFail();

    expect($movement->qty_change)->toBe(5)
        ->and($movement->note)->toBe('pelanggan batal')
        ->and($movement->user_id)->toBe($this->owner->id);
});

test('void tidak menghapus transaksi tapi mengubah statusnya', function () {
    $product = Product::factory()->create(['price' => 1000, 'stock' => 20]);
    $transaction = buatTransaksi($this->owner, $product);

    $voided = $this->void->handle($transaction, $this->owner, 'uang tidak cukup');

    expect(Transaction::count())->toBe(1)
        ->and($voided->status)->toBe(TransactionStatus::Dibatalkan)
        ->and($voided->void_reason)->toBe('uang tidak cukup')
        ->and($voided->voided_by)->toBe($this->owner->id)
        ->and($voided->voided_at)->not->toBeNull();
});

test('transaksi yang dibatalkan tidak masuk hitungan laporan', function () {
    $product = Product::factory()->create(['price' => 1000, 'stock' => 20]);
    $transaction = buatTransaksi($this->owner, $product);

    $this->void->handle($transaction, $this->owner, 'batal');

    expect(Transaction::completed()->count())->toBe(0)
        ->and(Transaction::count())->toBe(1);
});

test('kasir tidak boleh membatalkan transaksi', function () {
    $kasir = User::factory()->kasir()->create();
    $product = Product::factory()->create(['price' => 1000, 'stock' => 20]);
    $transaction = buatTransaksi($this->owner, $product);

    expect(fn () => $this->void->handle($transaction, $kasir, 'coba-coba'))
        ->toThrow(PosException::class);

    expect($transaction->fresh()->status)->toBe(TransactionStatus::Selesai);
});

test('alasan pembatalan wajib diisi', function () {
    $product = Product::factory()->create(['price' => 1000, 'stock' => 20]);
    $transaction = buatTransaksi($this->owner, $product);

    expect(fn () => $this->void->handle($transaction, $this->owner, '   '))
        ->toThrow(PosException::class);
});

test('transaksi tidak bisa dibatalkan dua kali', function () {
    $product = Product::factory()->create(['price' => 1000, 'stock' => 20]);
    $transaction = buatTransaksi($this->owner, $product, 2);

    $this->void->handle($transaction, $this->owner, 'batal pertama');

    expect(fn () => $this->void->handle($transaction->fresh(), $this->owner, 'batal lagi'))
        ->toThrow(PosException::class);

    // Stok hanya boleh kembali sekali, bukan dua kali.
    expect($product->fresh()->stock)->toBe(20);
});

test('void jasa tidak membuat pergerakan stok', function () {
    $jasa = Product::factory()->jasa()->create(['price' => 500]);
    $transaction = buatTransaksi($this->owner, $jasa, 10);

    $this->void->handle($transaction, $this->owner, 'salah hitung');

    expect(StockMovement::count())->toBe(0)
        ->and($transaction->fresh()->status)->toBe(TransactionStatus::Dibatalkan);
});
