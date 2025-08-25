<?php // app/Models/ServiceOffering.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOffering extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_type_id',
        'service_action_id',
        'name_override',
        'description_override',
        'default_price',
        'pricing_strategy',
        'default_price_per_sq_meter',
        'applicable_unit',
        'is_active'
    ];
    protected $casts = ['default_price' => 'decimal:2', 'default_price_per_sq_meter' => 'decimal:2', 'is_active' => 'boolean'];

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }
    public function serviceAction()
    {
        return $this->belongsTo(ServiceAction::class);
    }
    // Removed pricingRules relationship (customer-specific pricing disabled)
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Accessor for a display name
    public function getDisplayNameAttribute(): string
    {
        if ($this->name_override) return $this->name_override;
        return($this->serviceAction?->name ?: 'N/A Action');
    }
}
