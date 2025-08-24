<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTypeComposition extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_type_id',
        'product_composition_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function productComposition()
    {
        return $this->belongsTo(ProductComposition::class);
    }
}
