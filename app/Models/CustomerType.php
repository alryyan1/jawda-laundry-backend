<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Optional

class CustomerType extends Model
{
    use HasFactory;
    // use SoftDeletes; // If customer types can be soft-deleted

    protected $fillable = [
        'name',
        'description',
        // 'discount_percentage', // Example: if types have default discounts
        // 'requires_tax_id',   // Example: boolean for corporate types
    ];

    /**
     * Get all of the customers for the CustomerType.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get all of the pricing rules associated with this customer type.
     */
    public function pricingRules()
    {
        return $this->hasMany(PricingRule::class);
    }
}