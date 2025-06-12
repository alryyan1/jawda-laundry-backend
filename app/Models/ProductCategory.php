<?php // app/Models/ProductCategory.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description'];
    public function productTypes()
    {
        return $this->hasMany(ProductType::class);
    }
}
