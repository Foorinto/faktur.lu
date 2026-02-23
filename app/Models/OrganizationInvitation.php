<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrganizationInvitation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REVOKED = 'revoked';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'organization_id',
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

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public static function createForOrganization(Organization $organization, string $email, ?string $name = null): self
    {
        return self::create([
            'organization_id' => $organization->id,
            'email' => strtolower($email),
            'name' => $name,
            'token' => Str::random(64),
            'status' => self::STATUS_PENDING,
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function markAsAccepted(): void
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    public function revoke(): void
    {
        $this->update(['status' => self::STATUS_REVOKED]);
    }

    public function getAcceptUrl(): string
    {
        return route('collaborator.invitation.show', ['token' => $this->token]);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('expires_at', '>', now());
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => __('app.pending'),
            self::STATUS_ACCEPTED => __('app.accepted'),
            self::STATUS_REVOKED => __('app.revoked'),
            self::STATUS_EXPIRED => __('app.expired'),
            default => $this->status,
        };
    }
}
