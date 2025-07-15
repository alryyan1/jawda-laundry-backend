<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiningTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'capacity',
        'status',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(TableReservation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function activeReservation()
    {
        return $this->reservations()
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->latest()
            ->first();
    }

    public function activeOrder()
    {
        return $this->orders()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest()
            ->first();
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->is_active;
    }

    public function isOccupied(): bool
    {
        return $this->status === 'occupied';
    }

    public function isReserved(): bool
    {
        return $this->status === 'reserved';
    }

    public function isInMaintenance(): bool
    {
        return $this->status === 'maintenance';
    }
} 