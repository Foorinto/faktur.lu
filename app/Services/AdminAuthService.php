<?php

namespace App\Services;

use App\Models\AdminSession;
use App\Models\AdminLoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class AdminAuthService
{
    protected Google2FA $google2fa;

    public function __construct(Google2FA $google2fa)
    {
        $this->google2fa = $google2fa;
    }

    /**
     * Validate admin credentials.
     */
    public function validateCredentials(string $username, string $password): bool
    {
        $configUsername = config('admin.username');
        $configPasswordHash = config('admin.password_hash');

        if (empty($configUsername) || empty($configPasswordHash)) {
            return false;
        }

        return $username === $configUsername && Hash::check($password, $configPasswordHash);
    }

    /**
     * Verify 2FA code.
     */
    public function verify2fa(string $code): bool
    {
        $secret = config('admin.2fa_secret');

        if (empty($secret)) {
            return false;
        }

        return $this->google2fa->verifyKey($secret, $code, 1);
    }

    /**
     * Create a new admin session.
     */
    public function createSession(Request $request, bool $twoFactorConfirmed = false): string
    {
        $sessionId = Str::random(64);

        AdminSession::create([
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_activity' => now(),
            'two_factor_confirmed' => $twoFactorConfirmed,
            'created_at' => now(),
        ]);

        return $sessionId;
    }

    /**
     * Get the current admin session from request.
     */
    public function getSession(Request $request): ?AdminSession
    {
        $sessionId = $request->cookie(config('admin.session_cookie', 'admin_session'));

        if (!$sessionId) {
            return null;
        }

        return AdminSession::where('session_id', $sessionId)->valid()->first();
    }

    /**
     * Mark session as 2FA confirmed.
     */
    public function confirmTwoFactor(AdminSession $session): void
    {
        $session->update(['two_factor_confirmed' => true]);
    }

    /**
     * Touch the session to update last activity.
     */
    public function touchSession(AdminSession $session): void
    {
        $session->touchActivity();
    }

    /**
     * Destroy an admin session.
     */
    public function destroySession(Request $request): void
    {
        $sessionId = $request->cookie(config('admin.session_cookie', 'admin_session'));

        if ($sessionId) {
            AdminSession::where('session_id', $sessionId)->delete();
        }

        Cookie::queue(Cookie::forget(config('admin.session_cookie', 'admin_session')));
    }

    /**
     * Create the session cookie.
     */
    public function createSessionCookie(string $sessionId): \Symfony\Component\HttpFoundation\Cookie
    {
        $lifetime = (int) config('admin.session_lifetime', 30);

        return Cookie::make(
            config('admin.session_cookie', 'admin_session'),
            $sessionId,
            $lifetime,
            '/',
            null,
            true,  // secure
            true,  // httpOnly
            false, // raw
            'Strict' // sameSite
        );
    }

    /**
     * Check if IP is blocked.
     */
    public function isIpBlocked(string $ip): bool
    {
        return AdminLoginAttempt::isIpBlocked($ip);
    }

    /**
     * Get remaining block time for IP.
     */
    public function getBlockTimeRemaining(string $ip): ?int
    {
        return AdminLoginAttempt::getBlockTimeRemaining($ip);
    }

    /**
     * Record a login attempt.
     */
    public function recordLoginAttempt(Request $request, bool $successful, ?string $username = null): void
    {
        AdminLoginAttempt::record(
            $request->ip(),
            $successful,
            $request->userAgent(),
            $username
        );

        if ($successful) {
            AdminLoginAttempt::clearFailedAttempts($request->ip());
        }
    }

    /**
     * Check if admin is fully authenticated (login + 2FA).
     */
    public function isAuthenticated(Request $request): bool
    {
        $session = $this->getSession($request);

        return $session !== null && $session->two_factor_confirmed;
    }

    /**
     * Check if admin has passed first step (login) but not 2FA.
     */
    public function isPendingTwoFactor(Request $request): bool
    {
        $session = $this->getSession($request);

        return $session !== null && !$session->two_factor_confirmed;
    }

    /**
     * Clean up expired sessions.
     */
    public function cleanupExpiredSessions(): int
    {
        $lifetime = (int) config('admin.session_lifetime', 30);

        return AdminSession::where('last_activity', '<', now()->subMinutes($lifetime))->delete();
    }
}
