<?php // app/Models/PricingRule.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    use HasFactory;
    protected $fillable = [
        'service_offering_id',
        'customer_id',
        'price',
        'price_per_sq_meter'
    ];
    protected $casts = [
        'price' => 'decimal:2',
        'price_per_sq_meter' => 'decimal:2'
    ];
    
    public function serviceOffering()
    {
        return $this->belongsTo(ServiceOffering::class);
    }
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
