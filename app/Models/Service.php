<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes; // Optional: If services can be soft-deleted (e.g., discontinued)

class Service extends Model
{
    use HasFactory;
    // use SoftDeletes; // Uncomment if services can be soft-deleted

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'category', // e.g., "Washing", "Dry Cleaning", "Ironing"
        'duration_minutes', // Example: estimated time for the service
        'is_active', // Example: to enable/disable a service without deleting
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'decimal:2', // Ensure price is cast to a decimal with 2 places
        'is_active' => 'boolean',
    ];

    /**
     * Get all of the order items for the Service.
     * (Items where this service was included in an order)
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * The orders that include this service.
     * (Through the order_items pivot table)
     */
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items')
                    ->withPivot('quantity', 'price_at_order', 'sub_total')
                    ->withTimestamps();
    }
}