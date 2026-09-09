<?php

namespace App\Actions\Pos;

use App\Enums\StockMovementType;
use App\Enums\TransactionStatus;
use App\Exceptions\PosException;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Membatalkan (void) transaksi (§6.4).
 *
 * Baris transaksi tidak pernah dihapus — hanya berubah status, dan stok
 * dikembalikan lewat StockMovement bertipe pembatalan. Butuh role owner.
 */
class VoidTransaction
{
    public function __construct(private RecordStockMovement $stock) {}

    public function handle(Transaction $transaction, User $actor, string $reason): Transaction
    {
        if (! $actor->canManageStore()) {
            throw PosException::voidNotAuthorized();
        }

        if (trim($reason) === '') {
            throw PosException::voidReasonRequired();
        }

        if ($transaction->isVoided()) {
            throw PosException::alreadyVoided($transaction);
        }

        return DB::transaction(function () use ($transaction, $actor, $reason) {
            $transaction->load('items.product');

            foreach ($transaction->items as $item) {
                $this->stock->handle(
                    product: $item->product,
                    qtyChange: $item->qty,
                    type: StockMovementType::Pembatalan,
                    reference: $transaction,
                    actor: $actor,
                    note: $reason,
                );
            }

            $transaction->update([
                'status' => TransactionStatus::Dibatalkan,
                'voided_at' => now(),
                'voided_by' => $actor->id,
                'void_reason' => trim($reason),
            ]);

            return $transaction->fresh(['items']);
        });
    }
}
