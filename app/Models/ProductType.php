<?php // app/Models/ProductType.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_category_id',
        'name',
        'description',
        'is_dimension_based', // Changed from base_measurement_unit
        'is_active',
        'image_url',
    ];

    protected $casts = [
        'is_dimension_based' => 'boolean', // Add this cast
        'is_active' => 'boolean',
    ];
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }
    public function serviceOfferings()
    {
        return $this->hasMany(ServiceOffering::class);
    }
    // app/Models/ProductType.php
    public function predefinedSizes()
    {
        return $this->hasMany(PredefinedSize::class);
    }

    // Add inventory item relationship (one product type can have only one inventory item)
    public function inventoryItem()
    {
        return $this->hasOne(InventoryItem::class);
    }
}
