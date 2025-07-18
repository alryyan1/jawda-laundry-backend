<?php

namespace App\Policies;

use App\Models\NavigationItem;
use App\Models\User;

class NavigationItemPolicy
{
    /**
     * Determine whether the user can view any navigation items.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('navigation:view');
    }

    /**
     * Determine whether the user can view the navigation item.
     */
    public function view(User $user, NavigationItem $navigationItem): bool
    {
        return $user->can('navigation:view');
    }

    /**
     * Determine whether the user can create navigation items.
     */
    public function create(User $user): bool
    {
        return $user->can('navigation:create');
    }

    /**
     * Determine whether the user can update the navigation item.
     */
    public function update(User $user, NavigationItem $navigationItem): bool
    {
        return $user->can('navigation:update');
    }

    /**
     * Determine whether the user can delete the navigation item.
     */
    public function delete(User $user, NavigationItem $navigationItem): bool
    {
        return $user->can('navigation:delete') && !$navigationItem->is_default;
    }

    /**
     * Determine whether the user can manage user navigation permissions.
     */
    public function manageUserNavigation(User $user): bool
    {
        return $user->can('user-navigation:manage');
    }

    /**
     * Determine whether the user can view user navigation permissions.
     */
    public function viewUserNavigation(User $user, User $targetUser): bool
    {
        return $user->can('user-navigation:view') || $user->id === $targetUser->id;
    }
} 