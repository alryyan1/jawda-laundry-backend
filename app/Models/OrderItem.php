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
        'product_description_custom',
        'quantity',
        'length_meters',
        'width_meters',
        'calculated_price_per_unit_item',
        'sub_total',
        'notes',
        'status',
        'picked_up_quantity'
    ];
    protected $casts = [
        'quantity' => 'integer',
        'length_meters' => 'decimal:2',
        'width_meters' => 'decimal:2',
        'calculated_price_per_unit_item' => 'decimal:2',
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
}
