<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Accountant extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'name',
        'password',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the clients (users) this accountant has access to.
     */
    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'accountant_user')
            ->withPivot(['status', 'granted_at', 'revoked_at'])
            ->withTimestamps();
    }

    /**
     * Get only active clients.
     */
    public function activeClients(): BelongsToMany
    {
        return $this->clients()->wherePivot('status', 'active');
    }

    /**
     * Get all downloads made by this accountant.
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(AccountantDownload::class);
    }

    /**
     * Check if this accountant has access to a specific user.
     */
    public function hasAccessTo(User $user): bool
    {
        return $this->activeClients()->where('users.id', $user->id)->exists();
    }

    /**
     * Get the display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? $this->email;
    }
}
