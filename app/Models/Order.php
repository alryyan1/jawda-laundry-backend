<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Orders are good candidates for soft deletes

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_number',
        'customer_id',
        'user_id', // Staff member who created/processed the order
        'status',  // e.g., 'pending', 'processing', 'ready_for_pickup', 'completed', 'cancelled'
        'total_amount',
        'paid_amount',
        'payment_method', // e.g., 'cash', 'card', 'online'
        'payment_status', // e.g., 'pending', 'paid', 'partially_paid', 'refunded'
        'notes',
        'order_date',
        'due_date',
        'pickup_date',
        'delivery_address', // If you offer delivery
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'order_date' => 'datetime',
        'due_date' => 'datetime',
        'pickup_date' => 'datetime',
    ];

    /**
     * Get the customer that owns the Order.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed(); // Include soft-deleted customers if needed
    }

    /**
     * Get the user (staff member) who processed the Order.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all of the items for the Order.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * The services included in this order.
     * (Through the order_items pivot table)
     */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'order_items')
                    ->withPivot('quantity', 'price_at_order', 'sub_total')
                    ->withTimestamps();
    }

    /**
     * Scope a query to only include orders with a certain status.
     * Example: Order::status('pending')->get();
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Calculate the remaining amount due for the order.
     * Accessor: $order->amount_due
     */
    public function getAmountDueAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->paid_amount;
    }
}