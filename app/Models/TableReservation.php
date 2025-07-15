<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'dining_table_id',
        'customer_id',
        'order_id',
        'reservation_date',
        'party_size',
        'status',
        'notes',
        'contact_phone',
    ];

    protected $casts = [
        'reservation_date' => 'datetime',
    ];

    public function diningTable(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isSeated(): bool
    {
        return $this->status === 'seated';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
} 