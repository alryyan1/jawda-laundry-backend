<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'reference_number',
        'total_amount',
        'status',
        'purchase_date',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'purchase_date' => 'datetime:Y-m-d',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}