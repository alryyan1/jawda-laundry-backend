<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductComposition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Get the product type compositions that use this composition
     */
    public function productTypeCompositions()
    {
        return $this->hasMany(ProductTypeComposition::class);
    }
}
