<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait for multi-tenant data isolation.
 *
 * This trait automatically:
 * - Filters all queries by the authenticated user's ID
 * - Assigns user_id to new records on creation
 * - Provides the user() relationship
 *
 * Usage: Add `use BelongsToUser;` to any model that needs user isolation.
 */
trait BelongsToUser
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToUser(): void
    {
        // Add global scope to filter by authenticated user
        static::addGlobalScope('user', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where($builder->getModel()->getTable() . '.user_id', auth()->id());
            }
        });

        // Auto-assign user_id on creation
        static::creating(function (Model $model) {
            if (auth()->check() && empty($model->user_id)) {
                $model->user_id = auth()->id();
            }
        });
    }

    /**
     * Initialize the trait (for adding to fillable).
     */
    public function initializeBelongsToUser(): void
    {
        // Ensure user_id is fillable
        if (!in_array('user_id', $this->fillable)) {
            $this->fillable[] = 'user_id';
        }
    }

    /**
     * Get the user that owns this record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to query records for a specific user.
     * Useful for admin contexts or background jobs.
     */
    public function scopeForUser(Builder $query, int|User $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->withoutGlobalScope('user')->where('user_id', $userId);
    }

    /**
     * Scope to query all records regardless of user.
     * Use with caution - only for admin/system contexts.
     */
    public function scopeWithoutUserScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('user');
    }

    /**
     * Check if this record belongs to the given user.
     */
    public function belongsToUser(int|User $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return (int) $this->user_id === (int) $userId;
    }

    /**
     * Check if this record belongs to the authenticated user.
     */
    public function belongsToAuthUser(): bool
    {
        return auth()->check() && (int) $this->user_id === (int) auth()->id();
    }
}
