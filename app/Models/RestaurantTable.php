<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'number',
        'capacity',
        'description',
        'status',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all orders for this table
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    /**
     * Get active orders for this table
     */
    public function activeOrders()
    {
        return $this->orders()->whereIn('status', ['pending', 'processing']);
    }

    /**
     * Check if table is available
     */
    public function isAvailable()
    {
        return $this->status === 'available' && $this->is_active;
    }

    /**
     * Scope to get only active tables
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only available tables
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')->where('is_active', true);
    }
} 