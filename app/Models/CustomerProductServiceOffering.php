<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerProductServiceOffering extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'product_type_id',
        'service_action_id',
        'name_override',
        'description_override',
        'default_price',
        'default_price_per_sq_meter',
        'applicable_unit',
        'is_active',
        'custom_price',
        'custom_price_per_sq_meter',
        'valid_from',
        'valid_to',
        'min_quantity',
        'min_area_sq_meter',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_price' => 'decimal:2',
        'default_price_per_sq_meter' => 'decimal:2',
        'custom_price' => 'decimal:2',
        'custom_price_per_sq_meter' => 'decimal:2',
        'min_area_sq_meter' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function serviceAction()
    {
        return $this->belongsTo(ServiceAction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValidForDate($query, $date = null)
    {
        $date = $date ?? now()->toDateString();
        
        return $query->where(function ($q) use ($date) {
            $q->whereNull('valid_from')
              ->orWhere('valid_from', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('valid_to')
              ->orWhere('valid_to', '>=', $date);
        });
    }

    /**
     * Get the effective price (custom price if set, otherwise default price)
     */
    public function getEffectivePriceAttribute()
    {
        return $this->custom_price ?? $this->default_price;
    }

    /**
     * Get the effective price per square meter (custom price if set, otherwise default price)
     */
    public function getEffectivePricePerSqMeterAttribute()
    {
        return $this->custom_price_per_sq_meter ?? $this->default_price_per_sq_meter;
    }

    /**
     * Get the effective name (name override if set, otherwise service action name)
     */
    public function getEffectiveNameAttribute()
    {
        return $this->name_override ?? $this->serviceAction?->name;
    }

    /**
     * Get the effective description (description override if set, otherwise service action description)
     */
    public function getEffectiveDescriptionAttribute()
    {
        return $this->description_override ?? $this->serviceAction?->description;
    }
} 