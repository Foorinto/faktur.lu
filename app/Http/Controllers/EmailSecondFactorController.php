<?php

namespace App\Http\Controllers;

use App\Auth\EmailOtp;
use App\Auth\TrustedDevices;
use App\Models\AuditLog;
use App\Security\Mask;
use App\Security\SecurityAlerter;
use App\Security\TwoFactorPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Le code par e-mail depuis le profil : l'activer, le désactiver, et
 * demander un code pour une réauthentification à l'acte.
 */
class EmailSecondFactorController extends Controller
{
    public function __construct(
        private EmailOtp $emailOtp,
        private TrustedDevices $trustedDevices,
        private TwoFactorPolicy $policy,
        private SecurityAlerter $alerter,
    ) {}

    /** Un code pour confirmer une action sensible (voir App\Auth\Reauthenticator). */
    public function sendCode(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->secondFactor() !== 'email') {
            throw ValidationException::withMessages(['code' => __('app.email_otp.not_enabled')]);
        }

        $this->emailOtp->send($user);

        return response()->json([
            'sent' => true,
            'email' => Mask::email($user->email),
            'resend_after' => EmailOtp::RESEND_SECONDS,
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->usesEmailOtp()) {
            $user->forceFill(['email_otp_enabled_at' => now()])->save();
            AuditLogger::log(AuditLog::ACTION_EMAIL_OTP_ENABLED, $user);
            $this->fermerLesAutresSessions($request);
        }

        return back()->with('success', __('app.email_otp.enabled_flash'));
    }

    public function disable(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Le profil cache le bouton ; ceci arrête la requête forcée.
        if (! $this->policy->canDisableEmail($user)) {
            throw ValidationException::withMessages(['email_otp' => __('app.two_factor_notice.required')]);
        }

        if ($user->usesEmailOtp()) {
            $user->forceFill(['email_otp_enabled_at' => null])->save();
            $this->emailOtp->clear($user);
            $this->trustedDevices->forgetAll($user);
            AuditLogger::log(AuditLog::ACTION_EMAIL_OTP_DISABLED, $user);
            $this->fermerLesAutresSessions($request);
            $this->alerter->alert($user, SecurityAlerter::TWO_FACTOR_DISABLED);
        }

        return back()->with('success', __('app.email_otp.disabled_flash'));
    }

    /** Comme au changement de 2FA : les autres sessions tombent, la courante reste. */
    private function fermerLesAutresSessions(Request $request): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getKey())
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }
}
