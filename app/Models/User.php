<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'username',
        'password',
        'avatar_url', // Example: if users have avatars
      
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

    /**
     * Get navigation items that this user has explicit permissions for.
     */
    public function navigationItems(): BelongsToMany
    {
        return $this->belongsToMany(NavigationItem::class, 'user_navigation_permissions')
                    ->withPivot('is_granted')
                    ->withTimestamps();
    }

    /**
     * Get the user's roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(\Spatie\Permission\Models\Role::class, 'model_has_roles', 'model_id', 'role_id')
                    ->where('model_type', User::class);
    }

    /**
     * Get all accessible navigation items for this user.
     */
    public function getAccessibleNavigationItems(): array
    {
        $allNavigationItems = NavigationItem::active()->topLevel()->ordered()->with('activeChildren')->get();
        $accessibleItems = [];

        foreach ($allNavigationItems as $item) {
            if ($item->userCanAccess($this)) {
                $accessibleItem = $item->toArray();
                
                // Filter children that user can access
                if ($item->activeChildren->isNotEmpty()) {
                    $accessibleChildren = [];
                    foreach ($item->activeChildren as $child) {
                        if ($child->userCanAccess($this)) {
                            $accessibleChildren[] = $child->toArray();
                        }
                    }
                    $accessibleItem['children'] = $accessibleChildren;
                } else {
                    $accessibleItem['children'] = [];
                }
                
                $accessibleItems[] = $accessibleItem;
            }
        }

        return $accessibleItems;
    }

    // Example role check (simple)
    public function isAdmin(): bool
    {
            return $this->hasRole('admin');
        }

    public function isStaff(): bool
    {
        return $this->hasRole('staff') || $this->isAdmin(); // Admins are also staff
    }
    
    // Spatie provides methods like $user->hasRole('admin'), $user->can('edit articles'), etc.
    // So, our custom isAdmin(), isReceptionist() might become:
    public function isAdminSpatie(): bool
    {
        return $this->hasRole('admin'); // Assuming 'admin' is a role name in Spatie
    }

    /**
     * Boot method to set up navigation permissions for new users
     */
    protected static function boot()
    {
        parent::boot();

        // When a user is created, set up default navigation permissions
        static::created(function ($user) {
            // Import the seeder class
            \Database\Seeders\UserNavigationPermissionSeeder::setupDefaultNavigationForUser($user);
        });

        // When a user's role is updated, update navigation permissions
        static::updated(function ($user) {
            if ($user->wasChanged('roles')) {
                // Clear existing navigation permissions
                $user->navigationItems()->detach();
                // Set up new navigation permissions based on current role
                \Database\Seeders\UserNavigationPermissionSeeder::setupDefaultNavigationForUser($user);
            }
        });
    }
}