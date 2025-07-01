<?php

namespace App\Policies;

use App\Models\ProductType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductTypePolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('admin')) {
            return true; // Skip all checks
        }
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view product type');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProductType $productType): bool
    {
        return $user->hasPermissionTo('view product type');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create product type');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProductType $productType): bool
    {
        return $user->hasPermissionTo('update product type');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProductType $productType): bool
    {
        return $user->hasPermissionTo('delete product type');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProductType $productType): bool
    {
        return $user->hasPermissionTo('restore product type');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProductType $productType): bool
    {
        return $user->hasPermissionTo('force delete product type');
    }
}
