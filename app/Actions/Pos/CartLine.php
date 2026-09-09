<?php

namespace App\Actions\Pos;

/**
 * Satu baris keranjang kasir, sebelum divalidasi & dihitung ulang di server.
 */
class CartLine
{
    public function __construct(
        public int $productId,
        public int $qty,
    ) {}
}
