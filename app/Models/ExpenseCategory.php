<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model {
    use HasFactory;
    protected $fillable = ['name', 'description'];

    // Relationship to Expenses
    public function expenses() {
        return $this->hasMany(Expense::class, 'category', 'name'); // Links on the 'name' string
    }
}