<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function adjust(string $type, Product|ProductVariant $stockable, int $quantityChange, string $reason, ?Admin $admin = null): Inventory
    {
        if ($quantityChange === 0) {
            throw new \InvalidArgumentException('Quantity change cannot be zero.');
        }

        return DB::transaction(function () use ($type, $stockable, $quantityChange, $reason, $admin) {
            $inventory = Inventory::where('stockable_type', get_class($stockable))
                ->where('stockable_id', $stockable->id)
                ->first();

            if (! $inventory) {
                $threshold = $stockable instanceof Product ? $stockable->low_stock_threshold : $stockable->low_stock_threshold;
                $inventory = Inventory::create([
                    'stockable_type' => get_class($stockable),
                    'stockable_id' => $stockable->id,
                    'stock_on_hand' => (int) ($stockable->stock ?? 0),
                    'low_stock_threshold' => $threshold,
                ]);
            }

            $before = (int) $inventory->stock_on_hand;
            $after = $before + $quantityChange;

            if ($after < 0) {
                throw new \RuntimeException('Insufficient stock to complete this adjustment.');
            }

            $inventory->update([
                'stock_on_hand' => $after,
                'low_stock_threshold' => $stockable->low_stock_threshold,
            ]);

            $stockable->update(['stock' => $after]);

            InventoryTransaction::create([
                'stockable_type' => get_class($stockable),
                'stockable_id' => $stockable->id,
                'reference_type' => get_class($stockable),
                'reference_id' => $stockable->id,
                'type' => $type,
                'quantity_change' => $quantityChange,
                'stock_before' => $before,
                'stock_after' => $after,
                'reason' => $reason,
                'admin_id' => $admin?->id,
            ]);

            return $inventory->refresh();
        });
    }

    public function withReference(
        string $type,
        Product|ProductVariant $stockable,
        object $reference,
        int $quantityChange,
        string $reason
    ): void {
        DB::transaction(function () use ($type, $stockable, $reference, $quantityChange, $reason) {
            $inventory = Inventory::firstOrCreate(
                [
                    'stockable_type' => get_class($stockable),
                    'stockable_id' => $stockable->id,
                ],
                [
                    'stock_on_hand' => (int) $stockable->stock,
                    'low_stock_threshold' => $stockable->low_stock_threshold,
                ]
            );

            $before = (int) $inventory->stock_on_hand;
            $after = $before + $quantityChange;

            if ($after < 0 && $type === 'sale') {
                $after = 0;
            }

            $inventory->update(['stock_on_hand' => max(0, $after)]);
            $stockable->update(['stock' => max(0, $after)]);

            InventoryTransaction::create([
                'stockable_type' => get_class($stockable),
                'stockable_id' => $stockable->id,
                'reference_type' => get_class($reference),
                'reference_id' => $reference->id,
                'type' => $type,
                'quantity_change' => $quantityChange,
                'stock_before' => $before,
                'stock_after' => $after,
                'reason' => $reason,
            ]);
        });
    }
}