<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMainNav extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'key',
        'title',
        'icon',
        'route',
        'sort_order',
        'is_active',
        'permissions',
    ];

    protected $casts = [
        'title' => 'array',
        'permissions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns this navigation item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get active navigation items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get navigation items ordered by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Get the title for a specific language.
     */
    public function getTitleForLanguage(string $language = 'en'): string
    {
        return $this->title[$language] ?? $this->title['en'] ?? $this->key;
    }

    /**
     * Check if user has permission to see this navigation item.
     */
    public function userCanAccess(User $user): bool
    {
        if (empty($this->permissions)) {
            return true;
        }

        foreach ($this->permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
