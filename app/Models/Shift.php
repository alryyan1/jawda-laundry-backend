<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'opened_by',
        'closed_by',
        'opened_at',
        'closed_at',
        'opening_cash',
        'closing_cash',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}


