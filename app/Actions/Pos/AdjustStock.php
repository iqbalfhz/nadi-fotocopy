<?php

namespace App\Actions\Pos;

use App\Enums\StockMovementType;
use App\Exceptions\PosException;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Penyesuaian stok manual dari Panel Admin (§4.3).
 *
 * Selalu lewat RecordStockMovement dan wajib beralasan, supaya angka stok
 * tidak pernah berubah tanpa jejak (§7.2).
 */
class AdjustStock
{
    public function __construct(private RecordStockMovement $stock) {}

    public function handle(
        Product $product,
        int $newStock,
        User $actor,
        string $reason,
        StockMovementType $type = StockMovementType::Penyesuaian,
    ): Product {
        if (trim($reason) === '') {
            throw PosException::stockReasonRequired();
        }

        if ($newStock < 0) {
            throw PosException::negativeStock();
        }

        return DB::transaction(function () use ($product, $newStock, $actor, $reason, $type) {
            $delta = $newStock - $product->stock;

            $this->stock->handle(
                product: $product,
                qtyChange: $delta,
                type: $type,
                actor: $actor,
                note: trim($reason),
            );

            return $product->fresh();
        });
    }
}
