<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->numberBetween(1, 200) * 500;

        return [
            'transaction_number' => 'TRX-'.now()->format('Ymd').'-'.fake()->unique()->numberBetween(1, 9999),
            'user_id' => User::factory(),
            'payment_method' => PaymentMethod::Cash,
            'subtotal' => $total,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'total' => $total,
            'paid_amount' => $total,
            'change_amount' => 0,
            'status' => TransactionStatus::Selesai,
            'customer_phone' => null,
        ];
    }
}
