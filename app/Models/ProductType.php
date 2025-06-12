<?php // app/Models/ProductType.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    use HasFactory;
    protected $fillable = ['product_category_id', 'name', 'description', 'base_measurement_unit'];
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }
    public function serviceOfferings()
    {
        return $this->hasMany(ServiceOffering::class);
    }
}
