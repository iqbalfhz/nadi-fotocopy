<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $transaction_number
 * @property int $user_id
 * @property PaymentMethod $payment_method
 * @property int $subtotal
 * @property DiscountType|null $discount_type
 * @property int $discount_value
 * @property int $discount_amount
 * @property int $total
 * @property int $paid_amount
 * @property int $change_amount
 * @property TransactionStatus $status
 * @property string|null $customer_phone
 * @property Carbon|null $voided_at
 * @property int|null $voided_by
 * @property string|null $void_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, TransactionItem> $items
 */
#[Fillable([
    'transaction_number', 'user_id', 'payment_method', 'subtotal', 'discount_type',
    'discount_value', 'discount_amount', 'total', 'paid_amount', 'change_amount',
    'status', 'customer_phone', 'voided_at', 'voided_by', 'void_reason',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'discount_type' => DiscountType::class,
            'status' => TransactionStatus::class,
            'subtotal' => 'integer',
            'discount_value' => 'integer',
            'discount_amount' => 'integer',
            'total' => 'integer',
            'paid_amount' => 'integer',
            'change_amount' => 'integer',
            'voided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /**
     * @return HasMany<TransactionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function isVoided(): bool
    {
        return $this->status === TransactionStatus::Dibatalkan;
    }

    /**
     * Hanya transaksi selesai yang masuk laporan penjualan (§6.4).
     *
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::Selesai);
    }
}
