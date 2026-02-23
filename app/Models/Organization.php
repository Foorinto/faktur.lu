<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory;

    public const MAX_COLLABORATORS = 5;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Organization $org) {
            if (empty($org->slug)) {
                $org->slug = Str::slug($org->name) . '-' . Str::random(6);
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->whereNotNull('joined_at');
    }

    public function collaborators(): HasMany
    {
        return $this->activeMembers()->where('role', OrganizationMember::ROLE_COLLABORATOR);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    public function pendingInvitations(): HasMany
    {
        return $this->invitations()->where('status', OrganizationInvitation::STATUS_PENDING)
            ->where('expires_at', '>', now());
    }

    public function isOwner(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function isFull(): bool
    {
        return $this->collaborators()->count() >= self::MAX_COLLABORATORS;
    }

    public function hasMember(User $user): bool
    {
        return $this->activeMembers()->where('user_id', $user->id)->exists();
    }
}
