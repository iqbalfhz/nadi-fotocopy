<?php

namespace App\Actions\Pos;

use App\Enums\DiscountType;
use App\Exceptions\PosException;

/**
 * Hitung nominal diskon dari subtotal (§5.2).
 *
 * Selalu integer Rupiah, tidak pernah melebihi subtotal, dan dibulatkan
 * ke bawah untuk diskon persen agar tidak pernah menghasilkan pecahan rupiah.
 */
class CalculateDiscount
{
    public function handle(int $subtotal, ?DiscountType $type, int $value): int
    {
        if ($type === null || $value <= 0) {
            return 0;
        }

        $amount = match ($type) {
            DiscountType::Nominal => $value,
            DiscountType::Persen => $this->fromPercent($subtotal, $value),
        };

        return min($amount, $subtotal);
    }

    private function fromPercent(int $subtotal, int $percent): int
    {
        if ($percent > 100) {
            throw PosException::invalidPercentDiscount();
        }

        return intdiv($subtotal * $percent, 100);
    }
}
