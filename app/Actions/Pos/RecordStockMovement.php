<?php

namespace App\Actions\Pos;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu-satunya jalan mengubah stok produk (§7.1, §7.2).
 *
 * Mengubah kolom `stock` dengan update langsung dilarang — setiap perubahan
 * wajib melahirkan satu baris StockMovement supaya selisih stok bisa ditelusuri.
 */
class RecordStockMovement
{
    public function handle(
        Product $product,
        int $qtyChange,
        StockMovementType $type,
        ?Model $reference = null,
        ?User $actor = null,
        ?string $note = null,
    ): ?StockMovement {
        // Jasa tidak melacak stok — tidak ada yang perlu dicatat (§5.1).
        if (! $product->track_stock || $qtyChange === 0) {
            return null;
        }

        $product->stock += $qtyChange;
        $product->save();

        return StockMovement::create([
            'product_id' => $product->id,
            'type' => $type,
            'qty_change' => $qtyChange,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'user_id' => $actor?->id,
            'note' => $note,
        ]);
    }
}
