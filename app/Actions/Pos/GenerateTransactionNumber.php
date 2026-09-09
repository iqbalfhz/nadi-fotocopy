<?php

namespace App\Actions\Pos;

use App\Models\Transaction;
use Carbon\CarbonInterface;

/**
 * Menghasilkan nomor transaksi format TRX-YYYYMMDD-NNNN, urut per hari (§5.4).
 *
 * Harus dipanggil di dalam DB transaction: lockForUpdate menahan baris hari ini
 * sampai transaksi selesai, sehingga dua kasir bersamaan tidak dapat nomor sama.
 * Unique index pada kolom transaction_number adalah jaring pengaman terakhirnya.
 */
class GenerateTransactionNumber
{
    public function handle(?CarbonInterface $date = null): string
    {
        $date ??= now();
        $prefix = 'TRX-'.$date->format('Ymd').'-';

        $last = Transaction::query()
            ->where('transaction_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('transaction_number')
            ->value('transaction_number');

        $sequence = is_string($last)
            ? ((int) substr($last, strlen($prefix))) + 1
            : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
