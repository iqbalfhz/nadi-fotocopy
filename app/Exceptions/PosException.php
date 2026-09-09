<?php

namespace App\Exceptions;

use App\Models\Product;
use App\Models\Transaction;
use RuntimeException;

class PosException extends RuntimeException
{
    public static function emptyCart(): self
    {
        return new self('Transaksi harus punya minimal satu item.');
    }

    public static function invalidQty(string $productName): self
    {
        return new self("Jumlah untuk \"{$productName}\" harus lebih dari 0.");
    }

    public static function productInactive(Product $product): self
    {
        return new self("Produk \"{$product->name}\" sedang nonaktif dan tidak bisa dijual.");
    }

    public static function insufficientStock(Product $product, int $requested): self
    {
        return new self(
            "Stok \"{$product->name}\" tidak cukup: diminta {$requested}, tersisa {$product->stock}."
        );
    }

    public static function invalidPercentDiscount(): self
    {
        return new self('Diskon persen harus di antara 0 dan 100.');
    }

    public static function insufficientPayment(int $total, int $paid): self
    {
        return new self("Uang diterima (Rp {$paid}) kurang dari total (Rp {$total}).");
    }

    public static function alreadyVoided(Transaction $transaction): self
    {
        return new self("Transaksi {$transaction->transaction_number} sudah dibatalkan sebelumnya.");
    }

    public static function voidNotAuthorized(): self
    {
        return new self('Hanya owner yang boleh membatalkan transaksi.');
    }

    public static function voidReasonRequired(): self
    {
        return new self('Alasan pembatalan wajib diisi.');
    }

    public static function stockReasonRequired(): self
    {
        return new self('Alasan penyesuaian stok wajib diisi.');
    }

    public static function negativeStock(): self
    {
        return new self('Stok tidak boleh bernilai negatif.');
    }
}
