<?php

namespace App\Actions\Pos;

use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Menyusun laporan penjualan untuk satu rentang tanggal (§4.3).
 *
 * Hanya transaksi berstatus selesai yang dihitung — yang dibatalkan
 * tetap ada di riwayat tapi tidak pernah masuk laporan (§6.4).
 *
 * Rentang memakai timezone aplikasi (Asia/Jakarta), bukan UTC, supaya
 * batas hari sesuai jam operasional toko (§7.1).
 */
class BuildSalesReport
{
    /**
     * @return array{
     *     total: int,
     *     transaction_count: int,
     *     item_count: int,
     *     average: int,
     *     discount_total: int,
     *     voided_count: int
     * }
     */
    public function summary(CarbonInterface $from, CarbonInterface $to): array
    {
        $base = $this->scope($from, $to);

        $total = (int) (clone $base)->sum('total');
        $count = (clone $base)->count();
        $discount = (int) (clone $base)->sum('discount_amount');

        $items = (int) TransactionItem::query()
            ->whereIn('transaction_id', (clone $base)->select('id'))
            ->sum('qty');

        $voided = Transaction::query()
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->where('status', 'dibatalkan')
            ->count();

        return [
            'total' => $total,
            'transaction_count' => $count,
            'item_count' => $items,
            'average' => $count > 0 ? intdiv($total, $count) : 0,
            'discount_total' => $discount,
            'voided_count' => $voided,
        ];
    }

    /**
     * Omzet per metode pembayaran.
     *
     * Baris yang dikembalikan adalah model Transaction hasil agregasi, jadi
     * `payment_method` tetap ter-cast menjadi enum PaymentMethod — sementara
     * `total` dan `jumlah` adalah kolom agregat tambahan.
     *
     * @return Collection<int, Transaction>
     */
    public function byPaymentMethod(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $this->scope($from, $to)
            ->select('payment_method', DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as jumlah'))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Produk terlaris berdasarkan jumlah terjual.
     *
     * Baris berupa model TransactionItem dengan kolom agregat `qty` dan `total`.
     *
     * @return Collection<int, TransactionItem>
     */
    public function topProducts(CarbonInterface $from, CarbonInterface $to, int $limit = 10): Collection
    {
        return TransactionItem::query()
            ->whereIn('transaction_id', $this->scope($from, $to)->select('id'))
            ->select(
                'product_name',
                DB::raw('SUM(qty) as qty'),
                DB::raw('SUM(subtotal) as total'),
            )
            ->groupBy('product_name')
            ->orderByDesc('qty')
            ->limit($limit)
            ->get();
    }

    /**
     * Omzet harian untuk rincian per tanggal.
     *
     * Baris berupa model Transaction dengan kolom agregat `tanggal`,
     * `total`, dan `jumlah`.
     *
     * @return Collection<int, Transaction>
     */
    public function daily(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $this->scope($from, $to)
            ->select(
                DB::raw('DATE(created_at) as tanggal'),
                DB::raw('SUM(total) as total'),
                DB::raw('COUNT(*) as jumlah'),
            )
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();
    }

    /**
     * @return Builder<Transaction>
     */
    private function scope(CarbonInterface $from, CarbonInterface $to): Builder
    {
        return Transaction::query()
            ->completed()
            ->whereBetween('created_at', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ]);
    }
}
