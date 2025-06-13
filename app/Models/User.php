<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

// use Illuminate\Database\Eloquent\SoftDeletes; // Optional: If users can be soft-deleted

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable,HasRoles;
    // use SoftDeletes; // Uncomment if using soft deletes for users

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // Example: if you have roles like 'admin', 'staff'
        'avatar_url', // Example: if users have avatars
        'role'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // Automatically hashes password when set
    ];

    /**
     * Orders processed by this user (staff member).
     */
    public function ordersProcessed()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    /**
     * Customers managed or created by this user (staff member).
     * This assumes a 'user_id' foreign key on the 'customers' table.
     */
    public function managedCustomers()
    {
        return $this->hasMany(Customer::class, 'user_id');
    }

    // Example role check (simple)
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff' || $this->isAdmin(); // Admins are also staff
    }
    
    // Spatie provides methods like $user->hasRole('admin'), $user->can('edit articles'), etc.
    // So, our custom isAdmin(), isReceptionist() might become:
    public function isAdminSpatie(): bool
    {
        return $this->hasRole('admin'); // Assuming 'admin' is a role name in Spatie
    }
}