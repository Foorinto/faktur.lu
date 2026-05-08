<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($this->postVerificationRoute($user));
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->intended($this->postVerificationRoute($user));
    }

    /**
     * Apres verification de l'email, on envoie l'utilisateur sur l'onboarding s'il
     * en a un en cours (cad : onboarding_step set et onboarding_completed_at non
     * encore date). Sinon directement sur le dashboard.
     *
     * Note : les anciens utilisateurs (avant le systeme d'onboarding) ont
     * onboarding_step = NULL, donc tombent bien sur dashboard.
     */
    protected function postVerificationRoute($user): string
    {
        $needsOnboarding = $user->onboarding_step !== null && $user->onboarding_completed_at === null;

        return $needsOnboarding
            ? route('onboarding.show', absolute: false) . '?verified=1'
            : route('dashboard', absolute: false) . '?verified=1';
    }
}
