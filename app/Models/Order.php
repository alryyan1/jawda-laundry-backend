<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    } // Staff user
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function services()
    {
        return $this->belongsToMany(Service::class, 'order_items')->withPivot('quantity', 'price_at_order', 'sub_total')->withTimestamps();
    }
}
