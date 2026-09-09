<?php

namespace App\Enums;

enum DiscountType: string
{
    case Nominal = 'nominal';
    case Persen = 'persen';

    public function label(): string
    {
        return match ($this) {
            self::Nominal => 'Nominal (Rp)',
            self::Persen => 'Persen (%)',
        };
    }
}
