<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminLoginAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ip_address',
        'successful',
        'user_agent',
        'username',
        'created_at',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Record a login attempt.
     */
    public static function record(string $ip, bool $successful, ?string $userAgent = null, ?string $username = null): self
    {
        return self::create([
            'ip_address' => $ip,
            'successful' => $successful,
            'user_agent' => $userAgent,
            'username' => $username,
            'created_at' => now(),
        ]);
    }

    /**
     * Check if an IP is blocked due to too many failed attempts.
     */
    public static function isIpBlocked(string $ip): bool
    {
        $maxAttempts = config('admin.max_login_attempts', 3);
        $blockDuration = (int) config('admin.ip_block_duration', 60);

        $recentFailedAttempts = self::where('ip_address', $ip)
            ->where('successful', false)
            ->where('created_at', '>=', now()->subMinutes($blockDuration))
            ->count();

        return $recentFailedAttempts >= $maxAttempts;
    }

    /**
     * Get the time remaining until the IP block expires.
     */
    public static function getBlockTimeRemaining(string $ip): ?int
    {
        $blockDuration = (int) config('admin.ip_block_duration', 60);

        $lastFailedAttempt = self::where('ip_address', $ip)
            ->where('successful', false)
            ->where('created_at', '>=', now()->subMinutes($blockDuration))
            ->latest('created_at')
            ->first();

        if (!$lastFailedAttempt) {
            return null;
        }

        $unblockTime = $lastFailedAttempt->created_at->addMinutes($blockDuration);

        if ($unblockTime->isPast()) {
            return null;
        }

        return $unblockTime->diffInMinutes(now()) + 1;
    }

    /**
     * Clear failed attempts for an IP (called after successful login).
     */
    public static function clearFailedAttempts(string $ip): void
    {
        self::where('ip_address', $ip)
            ->where('successful', false)
            ->delete();
    }
}
