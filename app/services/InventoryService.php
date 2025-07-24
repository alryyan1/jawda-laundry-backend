<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventoryService
{
    public function addStock(int $itemId, float $quantity, float $unitCost = null, string $referenceType = null, int $referenceId = null, string $notes = null): bool
    {
        return DB::transaction(function () use ($itemId, $quantity, $unitCost, $referenceType, $referenceId, $notes) {
            $item = InventoryItem::findOrFail($itemId);
            
            // Create transaction
            InventoryTransaction::create([
                'inventory_item_id' => $itemId,
                'transaction_type' => 'purchase',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $unitCost ? $quantity * $unitCost : null,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'user_id' => Auth::id()
            ]);

            // Update stock level
            $item->addStock($quantity, $unitCost);
            
            return true;
        });
    }

    public function removeStock(int $itemId, float $quantity, string $referenceType = null, int $referenceId = null, string $notes = null): bool
    {
        return DB::transaction(function () use ($itemId, $quantity, $referenceType, $referenceId, $notes) {
            $item = InventoryItem::findOrFail($itemId);
            
            if ($item->current_stock < $quantity) {
                throw new \Exception("Insufficient stock. Available: {$item->current_stock}, Requested: {$quantity}");
            }

            // Create transaction
            InventoryTransaction::create([
                'inventory_item_id' => $itemId,
                'transaction_type' => 'sale',
                'quantity' => -$quantity,
                'unit_cost' => $item->cost_per_unit,
                'total_cost' => $item->cost_per_unit ? -($quantity * $item->cost_per_unit) : null,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'user_id' => Auth::id()
            ]);

            // Update stock level
            $item->removeStock($quantity);
            
            return true;
        });
    }

    public function adjustStock(int $itemId, float $quantity, string $transactionType = 'adjustment', string $notes = null): bool
    {
        return DB::transaction(function () use ($itemId, $quantity, $transactionType, $notes) {
            $item = InventoryItem::findOrFail($itemId);
            
            // Create transaction
            InventoryTransaction::create([
                'inventory_item_id' => $itemId,
                'transaction_type' => $transactionType,
                'quantity' => $quantity,
                'unit_cost' => $item->cost_per_unit,
                'total_cost' => $item->cost_per_unit ? $quantity * $item->cost_per_unit : null,
                'notes' => $notes,
                'user_id' => Auth::id()
            ]);

            // Update stock level
            if ($quantity > 0) {
                $item->addStock($quantity);
            } else {
                $item->removeStock(abs($quantity));
            }
            
            return true;
        });
    }

    public function getLowStockItems(): \Illuminate\Database\Eloquent\Collection
    {
        return InventoryItem::where('current_stock', '<=', DB::raw('min_stock_level'))
            ->where('is_active', true)
            ->with(['productType.category', 'supplier'])
            ->get();
    }

    public function getInventoryValue(): float
    {
        return InventoryItem::where('is_active', true)
            ->sum(DB::raw('current_stock * COALESCE(cost_per_unit, 0)'));
    }

    public function getInventoryTransactions(int $itemId = null, string $transactionType = null, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $query = InventoryTransaction::with(['inventoryItem', 'user'])
            ->orderBy('created_at', 'desc');

        if ($itemId) {
            $query->where('inventory_item_id', $itemId);
        }

        if ($transactionType) {
            $query->where('transaction_type', $transactionType);
        }

        return $query->limit($limit)->get();
    }
} 