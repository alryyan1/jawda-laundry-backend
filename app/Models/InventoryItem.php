<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_type_id',
        'sku',
        'description',
        'unit',
        'min_stock_level',
        'max_stock_level',
        'current_stock',
        'cost_per_unit',
        'supplier_id',
        'is_active'
    ];

    protected $casts = [
        'min_stock_level' => 'decimal:2',
        'max_stock_level' => 'decimal:2',
        'current_stock' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    protected $appends = [
        'total_value',
        'is_low_stock'
    ];

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

  

    // Check if stock is low
    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->min_stock_level;
    }

    // Get total value attribute
    public function getTotalValueAttribute(): float
    {
        return $this->current_stock * ($this->cost_per_unit ?? 0);
    }

    // Get is low stock attribute
    public function getIsLowStockAttribute(): bool
    {
        return $this->isLowStock();
    }

    // Add stock
    public function addStock(float $quantity, float $unitCost = null): void
    {
        $this->increment('current_stock', $quantity);
        
        if ($unitCost && !$this->cost_per_unit) {
            $this->update(['cost_per_unit' => $unitCost]);
        }
    }

    // Remove stock
    public function removeStock(float $quantity): void
    {
        if ($this->current_stock < $quantity) {
            throw new \Exception("Insufficient stock. Available: {$this->current_stock}, Requested: {$quantity}");
        }
        
        $this->decrement('current_stock', $quantity);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
