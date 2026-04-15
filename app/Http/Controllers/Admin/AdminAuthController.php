<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAuthService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuthController extends Controller
{
    public function __construct(
        protected AdminAuthService $adminAuth,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Show the admin login form.
     */
    public function showLogin(Request $request): Response|RedirectResponse|\Illuminate\Http\Response
    {
        // If already authenticated, redirect to dashboard
        if ($this->adminAuth->isAuthenticated($request)) {
            return redirect()->route('admin.dashboard');
        }

        // If pending 2FA, redirect to 2FA page
        if ($this->adminAuth->isPendingTwoFactor($request)) {
            return redirect()->route('admin.2fa');
        }

        // Use simple Blade view to avoid HTTP/2 issues on shared hosting
        if (!$request->header('X-Inertia')) {
            return response()->view('admin.login', [
                'error' => session('error'),
                'loginUrl' => route('admin.login'),
                'csrfToken' => csrf_token(),
            ]);
        }

        return Inertia::render('Admin/Login', [
            'error' => session('error'),
        ]);
    }

    /**
     * Handle admin login attempt.
     */
    public function login(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->input('username');
        $password = $request->input('password');
        $isAjax = $request->expectsJson();

        // Check if IP is blocked
        if ($this->adminAuth->isIpBlocked($request->ip())) {
            $remaining = $this->adminAuth->getBlockTimeRemaining($request->ip());
            $msg = "IP bloquée. Réessayez dans {$remaining} minutes.";
            return $isAjax
                ? response()->json(['message' => $msg], 403)
                : back()->with('error', $msg);
        }

        // Validate credentials
        if (!$this->adminAuth->validateCredentials($username, $password)) {
            $this->adminAuth->recordLoginAttempt($request, false, $username);
            $this->auditLogger->log('admin_login_failed', null, [
                'username' => $username,
                'ip' => $request->ip(),
            ]);

            if ($this->adminAuth->isIpBlocked($request->ip())) {
                $remaining = $this->adminAuth->getBlockTimeRemaining($request->ip());
                $msg = "IP bloquée après trop de tentatives. Réessayez dans {$remaining} minutes.";
                return $isAjax
                    ? response()->json(['message' => $msg], 403)
                    : back()->with('error', $msg);
            }

            return $isAjax
                ? response()->json(['message' => 'Identifiants incorrects.'], 422)
                : back()->with('error', 'Identifiants incorrects.');
        }

        // Create session (not yet 2FA confirmed)
        $sessionId = $this->adminAuth->createSession($request, false);

        $this->auditLogger->log('admin_login_step1', null, [
            'ip' => $request->ip(),
        ]);

        $cookie = $this->adminAuth->createSessionCookie($sessionId);

        if ($isAjax) {
            return response()->json([
                'redirect' => route('admin.2fa'),
            ])->withCookie($cookie);
        }

        return redirect()->route('admin.2fa')->withCookie($cookie);
    }

    /**
     * Show the 2FA challenge form.
     */
    public function showTwoFactor(Request $request): Response|RedirectResponse
    {
        // If already fully authenticated, redirect to dashboard
        if ($this->adminAuth->isAuthenticated($request)) {
            return redirect()->route('admin.dashboard');
        }

        // If not pending 2FA, redirect to login
        if (!$this->adminAuth->isPendingTwoFactor($request)) {
            return redirect()->route('admin.login');
        }

        return Inertia::render('Admin/TwoFactorChallenge', [
            'error' => session('error'),
        ]);
    }

    /**
     * Verify 2FA code.
     */
    public function verifyTwoFactor(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        // Check if pending 2FA
        if (!$this->adminAuth->isPendingTwoFactor($request)) {
            return redirect()->route('admin.login');
        }

        $code = $request->input('code');

        // Verify the code
        if (!$this->adminAuth->verify2fa($code)) {
            $this->auditLogger->log('admin_2fa_failed', null, [
                'ip' => $request->ip(),
            ]);

            return back()->with('error', 'Code 2FA incorrect.');
        }

        // Mark session as 2FA confirmed
        $session = $this->adminAuth->getSession($request);
        if ($session) {
            $this->adminAuth->confirmTwoFactor($session);
        }

        // Record successful login
        $this->adminAuth->recordLoginAttempt($request, true);
        $this->auditLogger->log('admin_login_success', null, [
            'ip' => $request->ip(),
        ]);

        return redirect()->route('admin.dashboard');
    }

    /**
     * Handle admin logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        $this->auditLogger->log('admin_logout', null, [
            'ip' => $request->ip(),
        ]);

        $this->adminAuth->destroySession($request);

        return redirect()->route('admin.login')
            ->with('success', 'Déconnexion réussie.')
            ->withCookie(Cookie::forget(config('admin.session_cookie', 'admin_session')));
    }
}
