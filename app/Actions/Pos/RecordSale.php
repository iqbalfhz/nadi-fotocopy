<?php

namespace App\Actions\Pos;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\StockMovementType;
use App\Enums\TransactionStatus;
use App\Exceptions\PosException;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Menyimpan satu transaksi penjualan POS (§6.2).
 *
 * Semua nilai dihitung ulang di server dari harga di database — angka yang
 * datang dari layar kasir tidak pernah dipercaya (§7.1). Pembuatan transaksi,
 * pencatatan item, dan pemotongan stok berjalan dalam satu DB transaction,
 * sehingga tidak mungkin ada transaksi tersimpan tanpa stok terpotong.
 */
class RecordSale
{
    public function __construct(
        private GenerateTransactionNumber $numbers,
        private CalculateDiscount $discounts,
        private RecordStockMovement $stock,
    ) {}

    /**
     * @param  array<int, CartLine>  $lines
     */
    public function handle(
        User $cashier,
        array $lines,
        PaymentMethod $paymentMethod,
        ?DiscountType $discountType = null,
        int $discountValue = 0,
        int $paidAmount = 0,
        ?string $customerPhone = null,
    ): Transaction {
        if ($lines === []) {
            throw PosException::emptyCart();
        }

        return DB::transaction(function () use (
            $cashier, $lines, $paymentMethod, $discountType, $discountValue, $paidAmount, $customerPhone
        ) {
            $products = $this->lockProducts($lines);
            $items = $this->buildItems($lines, $products);

            $subtotal = array_sum(array_column($items, 'subtotal'));
            $discountAmount = $this->discounts->handle($subtotal, $discountType, $discountValue);
            $total = $subtotal - $discountAmount;

            // QRIS/Debit ditandai lunas sebesar total; hanya Cash yang punya kembalian (§4.2).
            $paid = $paymentMethod->needsCashInput() ? $paidAmount : $total;

            if ($paid < $total) {
                throw PosException::insufficientPayment($total, $paid);
            }

            $transaction = Transaction::create([
                'transaction_number' => $this->numbers->handle(),
                'user_id' => $cashier->id,
                'payment_method' => $paymentMethod,
                'subtotal' => $subtotal,
                'discount_type' => $discountAmount > 0 ? $discountType : null,
                'discount_value' => $discountAmount > 0 ? $discountValue : 0,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'paid_amount' => $paid,
                'change_amount' => $paid - $total,
                'status' => TransactionStatus::Selesai,
                'customer_phone' => $customerPhone,
            ]);

            foreach ($items as $item) {
                $transaction->items()->create([
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'price' => $item['price'],
                    'qty' => $item['qty'],
                    'subtotal' => $item['subtotal'],
                ]);

                $this->stock->handle(
                    product: $item['product'],
                    qtyChange: -$item['qty'],
                    type: StockMovementType::Penjualan,
                    reference: $transaction,
                    actor: $cashier,
                );
            }

            return $transaction->load('items');
        });
    }

    /**
     * Kunci baris produk supaya stok tidak berubah di tengah perhitungan.
     *
     * @param  array<int, CartLine>  $lines
     * @return array<int, Product>
     */
    private function lockProducts(array $lines): array
    {
        $ids = array_values(array_unique(array_map(fn (CartLine $line) => $line->productId, $lines)));

        return Product::query()
            ->whereIn('id', $ids)
            ->lockForUpdate()
            ->get()
            ->keyBy('id')
            ->all();
    }

    /**
     * Validasi tiap baris dan hitung subtotalnya dari harga di database.
     *
     * @param  array<int, CartLine>  $lines
     * @param  array<int, Product>  $products
     * @return array<int, array{product: Product, price: int, qty: int, subtotal: int}>
     */
    private function buildItems(array $lines, array $products): array
    {
        $items = [];

        foreach ($lines as $line) {
            $product = $products[$line->productId] ?? null;

            if (! $product instanceof Product) {
                throw PosException::emptyCart();
            }

            if ($line->qty < 1) {
                throw PosException::invalidQty($product->name);
            }

            if (! $product->is_active) {
                throw PosException::productInactive($product);
            }

            if (! $product->hasEnoughStock($line->qty)) {
                throw PosException::insufficientStock($product, $line->qty);
            }

            $items[] = [
                'product' => $product,
                'price' => $product->price,
                'qty' => $line->qty,
                'subtotal' => $product->price * $line->qty,
            ];
        }

        return $items;
    }
}
