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
     * Check if user is on the free Starter plan.
     */
    public function isStarter(): bool
    {
        return !$this->isPro();
    }

    /**
     * Check if user is on trial.
     */
    public function isOnTrial(): bool
    {
        return $this->onTrial('default');
    }

    /**
     * Get the current plan name.
     */
    public function getPlanAttribute(): string
    {
        if ($this->isPro()) {
            return 'pro';
        }

        return 'starter';
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
}
