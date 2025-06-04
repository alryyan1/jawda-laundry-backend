<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    public function orderItems() { return $this->hasMany(OrderItem::class); }
public function orders() { return $this->belongsToMany(Order::class, 'order_items')->withPivot('quantity', 'price_at_order', 'sub_total')->withTimestamps(); }
}
