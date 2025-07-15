<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // For soft deleting customers

class Customer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'customer_type_id',
        'address',
        'user_id', // Foreign key for the staff member who created/manages this customer (optional)
        'notes',   // Any additional notes about the customer
        'is_default', // New field for default customer
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        // 'date_of_birth' => 'date', // Example if you had such a field
        'is_default' => 'boolean',
    ];

    /**
     * Get all of the orders for the Customer.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the user (staff member) who created or manages this customer.
     * This relationship is optional and depends on your 'user_id' foreign key.
     */
    public function managedBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function CustomerType(){
        return $this->belongsTo(CustomerType::class);
    }
   
   
}