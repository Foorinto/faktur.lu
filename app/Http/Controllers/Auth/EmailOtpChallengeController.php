<?php

namespace App\Http\Controllers\Auth;

use App\Auth\EmailOtp;
use App\Auth\LoginDestination;
use App\Auth\TrustedDevices;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Security\Mask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Le défi par e-mail après le mot de passe, sur le modèle du défi 2FA de
 * Fortify : le mot de passe a été vérifié, la session retient qui est
 * attendu, et la connexion n'est ouverte qu'avec le bon code.
 *
 * Ses clés de session sont les siennes (`email_otp.*`, pas `login.id`) : le
 * défi de Fortify ne doit pas pouvoir être rejoué avec un compte qui n'a pas
 * d'application.
 */
class EmailOtpChallengeController extends Controller
{
    public function __construct(private EmailOtp $emailOtp, private TrustedDevices $trustedDevices) {}

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $this->challenged($request);

        if ($user === null) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorEmailChallenge', [
            'email' => Mask::email($user->email),
            'resendAfter' => $this->emailOtp->secondsBeforeResend($user),
            'status' => session('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->challenged($request);

        if ($user === null) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'remember_device' => ['nullable', 'boolean'],
        ]);

        if ($user->isSecurityLocked()) {
            $request->session()->forget('email_otp');

            throw ValidationException::withMessages(['code' => trans('auth.locked')]);
        }

        if (! $this->emailOtp->verify($user, $request->input('code'))) {
            $this->journaliser($user, $request, AuditLog::ACTION_EMAIL_OTP_FAILED, AuditLog::STATUS_FAILED);

            throw ValidationException::withMessages(['code' => __('app.email_otp.invalid')]);
        }

        Auth::guard('web')->login($user, (bool) $request->session()->get('email_otp.remember', false));
        $request->session()->regenerate();
        $request->session()->forget('email_otp');

        if ($request->boolean('remember_device')) {
            $this->trustedDevices->remember($user, $request);
            $this->journaliser($user, $request, AuditLog::ACTION_TRUSTED_DEVICE_ADDED);
        }

        return LoginDestination::redirect($user, $request);
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->challenged($request);

        if ($user === null) {
            return redirect()->route('login');
        }

        $this->emailOtp->send($user);

        return back()->with('status', 'resent');
    }

    private function challenged(Request $request): ?User
    {
        $id = $request->session()->get('email_otp.user_id');

        return $id ? User::find($id) : null;
    }

    private function journaliser(User $user, Request $request, string $action, string $status = AuditLog::STATUS_SUCCESS): void
    {
        AuditLog::create([
            'user_id' => $user->getKey(),
            'action' => $action,
            'auditable_type' => $user->getMorphClass(),
            'auditable_id' => $user->getKey(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $status,
        ]);
    }
}
