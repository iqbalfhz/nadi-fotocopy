<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Penjualan = 'penjualan';
    case Pembatalan = 'pembatalan';
    case Penyesuaian = 'penyesuaian';
    case StokMasuk = 'stok_masuk';

    public function label(): string
    {
        return match ($this) {
            self::Penjualan => 'Penjualan',
            self::Pembatalan => 'Pembatalan',
            self::Penyesuaian => 'Penyesuaian',
            self::StokMasuk => 'Stok Masuk',
        };
    }
}
