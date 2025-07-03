<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'expense_category_id',
        'description',
        'amount',
        'expense_date',
        'payment_method',
        'user_id', // The user who recorded the expense
    ];

    /**
     * The attributes that should be cast.
     * This ensures data is returned in a consistent format.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2', // Always handle money as a decimal or integer
        'expense_date' => 'date:Y-m-d', // Cast to a simple date string 'YYYY-MM-DD'
    ];

    /**
     * Get the user who recorded this expense.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}