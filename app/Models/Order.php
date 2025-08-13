<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Orders are good candidates for soft deletes
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\Log;
use App\Services\PricingService;

class Order extends Model
{
    use HasFactory , LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'daily_order_number',
        'customer_id',
        'table_id',
        'dining_table_id',
        'user_id',
        'status',
        'order_complete',       // Track if order is completed
        'order_type', // New field for dine-in/take-away/delivery
        'total_amount',
        'paid_amount',
        'payment_method',       // Ensure this is here
        'payment_status',       // Ensure this is here
        'notes',
        'category_sequences',   // Category-specific sequences
        'order_date',
        'due_date',
        'pickup_date',
        'delivered_date',       // Date when order was delivered
        'delivery_address',     // Ensure this is here if used
        'whatsapp_text_sent',
        'whatsapp_pdf_sent',
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
        'delivered_date' => 'datetime',
        'order_complete' => 'boolean',
        'whatsapp_text_sent' => 'boolean',
        'whatsapp_pdf_sent' => 'boolean',
        'category_sequences' => 'array',
    ];

    /**
     * Get the customer that owns the Order.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the table associated with the Order.
     */
    public function table()
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    /**
     * Get the dining table associated with the Order.
     */
    public function diningTable()
    {
        return $this->belongsTo(DiningTable::class);
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

    /**
     * Calculate the total amount from order items dynamically.
     * Accessor: $order->calculated_total_amount
     */
    public function getCalculatedTotalAmountAttribute(): float
    {
        $calculated = (float) $this->items()->sum('sub_total');
        
        // Debug logging
        Log::info('Calculating total amount for order:', [
            'order_id' => $this->id,
            'calculated_total' => $calculated,
            'items_count' => $this->items()->count(),
            'items_sum' => $this->items()->sum('sub_total'),
        ]);
        
        return $calculated;
    }

    /**
     * Recalculate and update the stored total_amount from order items
     */
    public function recalculateTotalAmount(): void
    {
        $this->total_amount = $this->calculated_total_amount;
        $this->saveQuietly(); // Use saveQuietly to avoid triggering the saved event
    }

    /**
     * Recalculate total amount by recalculating each order item's subtotal
     * This is especially important for dimension-based products
     */
    public function recalculateTotalAmountWithItemRecalculation(): void
    {
        $pricingService = app(PricingService::class);
        $totalAmount = 0;

        // Load items with their relationships
        $this->load(['items.serviceOffering.productType', 'customer']);

        foreach ($this->items as $item) {
            // Recalculate the price for this item, considering quantity
            $priceDetails = $pricingService->calculatePrice(
                $item->serviceOffering,
                $this->customer,
                $item->quantity,
                $item->length_meters,
                $item->width_meters
            );

            // Update the item's calculated price and subtotal
            $item->calculated_price_per_unit_item = $priceDetails['calculated_price_per_unit_item'];
            $item->sub_total = $priceDetails['sub_total'];
            $item->saveQuietly(); // Use saveQuietly to avoid triggering events

            $totalAmount += $priceDetails['sub_total'];
            
            Log::info('Recalculated order item:', [
                'order_item_id' => $item->id,
                'quantity' => $item->quantity,
                'length_meters' => $item->length_meters,
                'width_meters' => $item->width_meters,
                'calculated_price_per_unit' => $priceDetails['calculated_price_per_unit_item'],
                'subtotal' => $priceDetails['sub_total'],
                'product_type' => $item->serviceOffering->productType->name,
            ]);
        }

        // Update the order's total amount
        $this->total_amount = $totalAmount;
        $this->saveQuietly(); // Use saveQuietly to avoid triggering the saved event

        Log::info('Recalculated total amount with item recalculation:', [
            'order_id' => $this->id,
            'new_total_amount' => $totalAmount,
            'items_count' => $this->items->count(),
        ]);
    }



    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Generate the next daily order number for today
     */
    public static function generateDailyOrderNumber(): int
    {
        $today = now()->format('Y-m-d');
        
        // Count the number of orders for today
        $orderCount = self::whereDate('created_at', $today)
            ->whereNotNull('daily_order_number')
            ->count();
        
        return $orderCount + 1;
    }

    /**
     * Generate category-specific sequences for this order
     */
    public function generateCategorySequences(bool $isUpdate = false): void
    {
        $sequenceService = app(\App\Services\OrderSequenceService::class);
        $sequences = $sequenceService->generateOrderSequences($this, $isUpdate);
        $this->category_sequences = $sequences;
        $this->save();
    }

    /**
     * Get category sequences as a formatted string
     */
    public function getCategorySequencesString(): string
    {
        if (empty($this->category_sequences)) {
            return '';
        }

        return implode(', ', $this->category_sequences);
    }

    /**
     * Boot method to automatically set daily_order_number when creating and recalculate totals
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->daily_order_number)) {
                $order->daily_order_number = self::generateDailyOrderNumber();
            }
            
            // Set pickup_date to 3 days from now if not provided and order is not a customer payment
            if (empty($order->pickup_date) && $order->order_type !== 'customer_payment') {
                $order->pickup_date = now()->addDays(3);
            }
        });

        // Recalculate total when order is saved (but not when total_amount was explicitly changed)
        static::saved(function ($order) {
            if ($order->wasChanged('total_amount') === false) {
                $calculatedTotal = $order->calculated_total_amount;
                if ($order->total_amount != $calculatedTotal) {
                    $order->total_amount = $calculatedTotal;
                    $order->saveQuietly(); // Use saveQuietly to avoid infinite loop
                }
            }
        });

        // Removed the created event for category sequences since it's handled explicitly in the controller
        // static::created(function ($order) {
        //     // Generate category sequences after order is created
        //     if ($order->items()->count() > 0) {
        //         $order->generateCategorySequences();
        //     }
        // });
    }
}