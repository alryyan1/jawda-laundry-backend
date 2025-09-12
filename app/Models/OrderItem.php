<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'service_offering_id',
        'quantity',
        'sub_total',
        'notes',
        'status',
        'picked_up_quantity'
    ];
    protected $casts = [
        'quantity' => 'integer',
        'sub_total' => 'decimal:2',
        'status' => 'string',
        'picked_up_quantity' => 'integer'
    ];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function serviceOffering()
    {
        return $this->belongsTo(ServiceOffering::class);
    }

    /**
     * Get the calculated price per unit item (accessor for backward compatibility)
     */
    public function getCalculatedPricePerUnitItemAttribute()
    {
        return $this->serviceOffering ? (float) ($this->serviceOffering->default_price ?? 0) : 0;
    }

    /**
     * Boot method to recalculate order total when items are saved
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($orderItem) {
            if ($orderItem->order) {
                $orderItem->order->recalculateTotalAmount();
            }
        });

        static::deleted(function ($orderItem) {
            if ($orderItem->order) {
                $orderItem->order->recalculateTotalAmount();
            }
        });
    }
}
