<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerProductType extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'product_type_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the customer that owns this product type assignment
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the product type assigned to this customer
     */
    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    /**
     * Get the service offerings for this customer-product type combination
     */
    public function serviceOfferings()
    {
        return $this->hasMany(CustomerProductServiceOffering::class, 'product_type_id', 'product_type_id')
            ->where('customer_id', $this->customer_id);
    }
} 