<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AccountantInvitation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REVOKED = 'revoked';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'email',
        'name',
        'token',
        'status',
        'accepted_at',
        'expires_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user who created this invitation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a new invitation for a user.
     */
    public static function createForUser(User $user, string $email, ?string $name = null): self
    {
        return self::create([
            'user_id' => $user->id,
            'email' => strtolower($email),
            'name' => $name,
            'token' => Str::random(64),
            'status' => self::STATUS_PENDING,
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * Check if the invitation is still valid.
     */
    public function isValid(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->expires_at->isFuture();
    }

    /**
     * Check if expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Mark as accepted.
     */
    public function markAsAccepted(): void
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Mark as revoked.
     */
    public function revoke(): void
    {
        $this->update(['status' => self::STATUS_REVOKED]);
    }

    /**
     * Get the acceptance URL.
     */
    public function getAcceptUrl(): string
    {
        return route('accountant.accept', ['token' => $this->token]);
    }

    /**
     * Scope for pending invitations.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('expires_at', '>', now());
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_ACCEPTED => 'Acceptée',
            self::STATUS_REVOKED => 'Révoquée',
            self::STATUS_EXPIRED => 'Expirée',
            default => $this->status,
        };
    }
}
