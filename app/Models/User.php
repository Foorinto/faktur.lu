<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, SoftDeletes, Billable;

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'locale',
        'account_status',
        'trial_ends_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'two_factor_enabled',
    ];

    /**
     * Check if the user has two-factor authentication enabled.
     */
    public function getTwoFactorEnabledAttribute(): bool
    {
        return $this->hasEnabledTwoFactorAuthentication();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'trial_ends_at' => 'datetime',
        ];
    }

    /**
     * Get the business settings for this user.
     */
    public function businessSettings(): HasOne
    {
        return $this->hasOne(BusinessSettings::class);
    }

    /**
     * Get the email settings for this user.
     */
    public function emailSettings(): HasOne
    {
        return $this->hasOne(EmailSettings::class);
    }

    /**
     * Get the clients for this user.
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * Get the invoices for this user.
     * Note: Named userInvoices to avoid conflict with Laravel Cashier's invoices() method.
     */
    public function userInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the quotes for this user.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * Get the expenses for this user.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get the time entries for this user.
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Get the audit exports for this user.
     */
    public function auditExports(): HasMany
    {
        return $this->hasMany(AuditExport::class);
    }

    /**
     * Check if user has an active Pro subscription.
     */
    public function isPro(): bool
    {
        return $this->subscribed('default');
    }

    /**
     * Check if user is on the free Essentiel plan (not Pro).
     */
    public function isEssentiel(): bool
    {
        return $this->subscribed('default') && !$this->subscribedToPrice(config('services.stripe.pro_monthly')) && !$this->subscribedToPrice(config('services.stripe.pro_yearly'));
    }

    /**
     * Check if user is on the generic trial (14 days without subscription).
     */
    public function isOnGenericTrial(): bool
    {
        return $this->trial_ends_at
            && $this->trial_ends_at->isFuture()
            && !$this->subscribed('default');
    }

    /**
     * Check if user is on Stripe subscription trial.
     */
    public function isOnSubscriptionTrial(): bool
    {
        return $this->onTrial('default');
    }

    /**
     * Check if user is on any trial (generic or subscription).
     */
    public function isOnTrial(): bool
    {
        return $this->isOnGenericTrial() || $this->isOnSubscriptionTrial();
    }

    /**
     * Get the number of days remaining on trial.
     */
    public function trialDaysRemaining(): int
    {
        if (!$this->trial_ends_at) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->trial_ends_at, false));
    }

    /**
     * Check if the generic trial has expired (no active subscription).
     */
    public function isTrialExpired(): bool
    {
        return $this->trial_ends_at
            && $this->trial_ends_at->isPast()
            && !$this->subscribed('default');
    }

    /**
     * Check if user can fully access the app (has subscription or is on trial).
     */
    public function canAccessApp(): bool
    {
        return $this->subscribed('default') || $this->isOnGenericTrial();
    }

    /**
     * Check if user account is in read-only mode (trial expired, no subscription).
     */
    public function isReadOnly(): bool
    {
        return $this->isTrialExpired();
    }

    /**
     * Get the current plan name.
     */
    public function getPlanAttribute(): string
    {
        if ($this->isPro()) {
            return 'pro';
        }

        if ($this->isOnGenericTrial()) {
            return 'trial';
        }

        return 'essentiel';
    }

    /**
     * Get the accountants who have access to this user's data.
     */
    public function accountants(): BelongsToMany
    {
        return $this->belongsToMany(Accountant::class, 'accountant_user')
            ->withPivot(['status', 'granted_at', 'revoked_at'])
            ->withTimestamps();
    }

    /**
     * Get only active accountants.
     */
    public function activeAccountants(): BelongsToMany
    {
        return $this->accountants()->wherePivot('status', 'active');
    }

    /**
     * Get accountant invitations.
     */
    public function accountantInvitations(): HasMany
    {
        return $this->hasMany(AccountantInvitation::class);
    }

    /**
     * Get accountant downloads for this user.
     */
    public function accountantDownloads(): HasMany
    {
        return $this->hasMany(AccountantDownload::class);
    }

    /**
     * Get support tickets for this user.
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * Get the count of support tickets with unread admin responses.
     */
    public function getUnreadSupportCountAttribute(): int
    {
        return $this->supportTickets()
            ->whereHas('messages', function ($query) {
                $query->where('is_internal', false)
                    ->where('sender_type', '!=', self::class)
                    ->whereColumn('created_at', '>', 'support_tickets.user_last_read_at');
            })
            ->orWhere(function ($query) {
                $query->whereNull('user_last_read_at')
                    ->whereHas('messages', function ($q) {
                        $q->where('is_internal', false)
                            ->where('sender_type', '!=', User::class);
                    });
            })
            ->count();
    }
}
