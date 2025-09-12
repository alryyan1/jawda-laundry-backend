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
        'image_url',
    ];

    protected $casts = [
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

    public function compositions()
    {
        return $this->hasMany(ProductTypeComposition::class);
    }

    public function activeCompositions()
    {
        return $this->hasMany(ProductTypeComposition::class)->where('is_active', true);
    }

}
