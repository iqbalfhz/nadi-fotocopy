<?php

namespace App\Support;

/**
 * Format nilai uang integer (§5.4) menjadi teks Rupiah.
 */
class Rupiah
{
    public static function format(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /**
     * Tanpa awalan "Rp" — untuk kolom tabel yang sudah punya header Rupiah.
     */
    public static function plain(int $amount): string
    {
        return number_format($amount, 0, ',', '.');
    }
}
