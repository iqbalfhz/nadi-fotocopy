<?php

namespace App\Enums;

enum ProductType: string
{
    case Produk = 'produk';
    case Jasa = 'jasa';

    public function label(): string
    {
        return match ($this) {
            self::Produk => 'Produk',
            self::Jasa => 'Jasa',
        };
    }

    /**
     * Apakah jenis ini melacak stok secara default (§5.1).
     *
     * Jasa (fotocopy, print, jilid) tidak punya stok — kalau dilacak,
     * semuanya akan tampil "Habis" di katalog publik.
     */
    public function tracksStockByDefault(): bool
    {
        return $this === self::Produk;
    }
}
