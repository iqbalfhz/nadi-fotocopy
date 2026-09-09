<?php

use App\Actions\Pos\BuildSalesReport;
use App\Actions\Pos\CartLine;
use App\Actions\Pos\RecordSale;
use App\Actions\Pos\VoidTransaction;
use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->report = app(BuildSalesReport::class);
});

function jual(User $owner, Product $product, int $qty, PaymentMethod $method = PaymentMethod::Qris)
{
    return app(RecordSale::class)->handle(
        cashier: $owner,
        lines: [new CartLine($product->id, $qty)],
        paymentMethod: $method,
    );
}

test('kasir ditolak membuka laporan', function () {
    $kasir = User::factory()->kasir()->create();

    $this->actingAs($kasir)->get(route('admin.laporan'))->assertForbidden();
});

test('owner bisa membuka laporan', function () {
    $this->actingAs($this->owner)->get(route('admin.laporan'))->assertOk();
});

test('ringkasan menjumlahkan omzet hari ini', function () {
    $product = Product::factory()->create(['price' => 5000, 'stock' => 100]);

    jual($this->owner, $product, 2);
    jual($this->owner, $product, 3);

    $summary = $this->report->summary(Carbon::today(), Carbon::today());

    expect($summary['total'])->toBe(25000)
        ->and($summary['transaction_count'])->toBe(2)
        ->and($summary['item_count'])->toBe(5)
        ->and($summary['average'])->toBe(12500);
});

test('transaksi yang dibatalkan tidak dihitung di laporan', function () {
    $product = Product::factory()->create(['price' => 10000, 'stock' => 100]);

    jual($this->owner, $product, 1);
    $dibatalkan = jual($this->owner, $product, 5);

    app(VoidTransaction::class)->handle($dibatalkan, $this->owner, 'salah input');

    $summary = $this->report->summary(Carbon::today(), Carbon::today());

    expect($summary['total'])->toBe(10000)
        ->and($summary['transaction_count'])->toBe(1)
        ->and($summary['voided_count'])->toBe(1);
});

test('omzet dikelompokkan per metode pembayaran', function () {
    $product = Product::factory()->create(['price' => 1000, 'stock' => 100]);

    jual($this->owner, $product, 3, PaymentMethod::Qris);
    jual($this->owner, $product, 2, PaymentMethod::Debit);
    jual($this->owner, $product, 5, PaymentMethod::Debit);

    $rows = $this->report->byPaymentMethod(Carbon::today(), Carbon::today())
        ->keyBy('payment_method');

    expect((int) $rows['qris']->total)->toBe(3000)
        ->and((int) $rows['debit']->total)->toBe(7000)
        ->and((int) $rows['debit']->jumlah)->toBe(2);
});

test('produk terlaris diurutkan berdasarkan jumlah terjual', function () {
    $laris = Product::factory()->create(['name' => 'Fotocopy A4', 'price' => 300, 'stock' => 500]);
    $sepi = Product::factory()->create(['name' => 'Jilid Spiral', 'price' => 15000, 'stock' => 50]);

    jual($this->owner, $laris, 100);
    jual($this->owner, $sepi, 2);

    $top = $this->report->topProducts(Carbon::today(), Carbon::today());

    expect($top->first()->product_name)->toBe('Fotocopy A4')
        ->and((int) $top->first()->qty)->toBe(100);
});

test('transaksi di luar rentang tanggal tidak ikut terhitung', function () {
    $product = Product::factory()->create(['price' => 1000, 'stock' => 100]);

    Carbon::setTestNow(Carbon::today()->subDays(10)->setHour(10));
    jual($this->owner, $product, 5);
    Carbon::setTestNow();

    jual($this->owner, $product, 2);

    $hariIni = $this->report->summary(Carbon::today(), Carbon::today());

    expect($hariIni['total'])->toBe(2000)
        ->and($hariIni['transaction_count'])->toBe(1);
});

test('transaksi larut malam tetap masuk hari yang sama menurut waktu Jakarta', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta');

    $product = Product::factory()->create(['price' => 1000, 'stock' => 100]);

    // 23.30 waktu Jakarta — kalau laporan memakai UTC, ini akan bocor ke hari berikutnya.
    Carbon::setTestNow(Carbon::today()->setTime(23, 30));
    jual($this->owner, $product, 4);
    Carbon::setTestNow();

    $summary = $this->report->summary(Carbon::today(), Carbon::today());

    expect($summary['total'])->toBe(4000)
        ->and($summary['transaction_count'])->toBe(1);
});

test('total diskon direkap di laporan', function () {
    $product = Product::factory()->create(['price' => 10000, 'stock' => 100]);

    app(RecordSale::class)->handle(
        cashier: $this->owner,
        lines: [new CartLine($product->id, 1)],
        paymentMethod: PaymentMethod::Qris,
        discountType: DiscountType::Nominal,
        discountValue: 2500,
    );

    $summary = $this->report->summary(Carbon::today(), Carbon::today());

    expect($summary['discount_total'])->toBe(2500)
        ->and($summary['total'])->toBe(7500);
});

test('halaman laporan menampilkan omzet', function () {
    $product = Product::factory()->create(['price' => 12500, 'stock' => 10]);
    jual($this->owner, $product, 2);

    Livewire::actingAs($this->owner)
        ->test('pages::admin.laporan')
        ->assertSeeText('Rp 25.000');
});

test('mengganti periode mengubah rentang tanggal', function () {
    Livewire::actingAs($this->owner)
        ->test('pages::admin.laporan')
        ->assertSet('from', Carbon::today()->toDateString())
        ->set('period', 'bulanan')
        ->assertSet('from', Carbon::today()->startOfMonth()->toDateString())
        ->assertSet('to', Carbon::today()->endOfMonth()->toDateString());
});
