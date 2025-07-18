<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NavigationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'title',
        'icon',
        'route',
        'parent_id',
        'sort_order',
        'is_active',
        'is_default',
        'permissions',
    ];

    protected $casts = [
        'title' => 'array',
        'permissions' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get the parent navigation item.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(NavigationItem::class, 'parent_id');
    }

    /**
     * Get the child navigation items.
     */
    public function children(): HasMany
    {
        return $this->hasMany(NavigationItem::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get all active child navigation items.
     */
    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    /**
     * Get users who have explicit permissions for this navigation item.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_navigation_permissions')
                    ->withPivot('is_granted')
                    ->withTimestamps();
    }

    /**
     * Get the title for a specific language.
     */
    public function getTitle(string $language = 'en'): string
    {
        return $this->title[$language] ?? $this->title['en'] ?? $this->key;
    }

    /**
     * Check if this navigation item requires specific permissions.
     */
    public function hasPermissionRequirements(): bool
    {
        return !empty($this->permissions);
    }

    /**
     * Get required permissions as array.
     */
    public function getRequiredPermissions(): array
    {
        return $this->permissions ?? [];
    }

    /**
     * Scope for active navigation items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for top-level navigation items.
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for ordering by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Check if user can access this navigation item.
     */
    public function userCanAccess(User $user): bool
    {
        // Check explicit user permission first
        $userPermission = $this->users()->where('user_id', $user->id)->first();
        if ($userPermission) {
            return $userPermission->pivot->is_granted;
        }

        // If no explicit permission, check if user has required permissions
        if ($this->hasPermissionRequirements()) {
            foreach ($this->getRequiredPermissions() as $permission) {
                if (!$user->can($permission)) {
                    return false;
                }
            }
        }

        return true;
    }
} 