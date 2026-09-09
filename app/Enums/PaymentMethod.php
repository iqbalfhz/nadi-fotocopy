<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Qris = 'qris';
    case Debit = 'debit';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Qris => 'QRIS',
            self::Debit => 'Debit',
        };
    }

    /**
     * Hanya Cash yang butuh input uang diterima & hitung kembalian (§4.2).
     */
    public function needsCashInput(): bool
    {
        return $this === self::Cash;
    }
}
