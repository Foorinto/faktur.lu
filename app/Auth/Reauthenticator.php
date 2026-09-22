<?php

namespace App\Auth;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\TwoFactorAuthenticationProvider;

/**
 * Réauthentification à l'acte : le mot de passe, plus le code 2FA si elle est
 * active, au moment d'un geste sensible.
 *
 * Une session ouverte n'est pas une preuve d'identité : elle peut avoir été
 * volée, ou laissée ouverte sur un poste partagé. Avant de changer l'IBAN,
 * l'adresse email, le mot de passe, de désactiver la 2FA ou d'exporter les
 * données, on redemande ce que seul le titulaire possède.
 *
 * ⚠️ C'est le seul endroit où cette règle est écrite. La page de confirmation
 * de Laravel, celle de Fortify et les formulaires qui posent la question en
 * ligne passent tous par ici. Deux implémentations divergeraient un jour, et
 * le chemin le plus faible serait celui que l'attaquant emprunterait.
 *
 * Le code 2FA accepté est celui de l'application d'authentification, ou un
 * code de secours, consommé comme à la connexion. Sans cette seconde porte,
 * un utilisateur sans son téléphone ne pourrait plus rien changer.
 */
class Reauthenticator
{
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 60;

    public function __construct(
        private TwoFactorAuthenticationProvider $provider,
        private EmailOtp $emailOtp,
    ) {}

    /**
     * Vérifie, ou lève une ValidationException portée par le bon champ.
     *
     * Les noms de champs sont paramétrables parce que la page de confirmation
     * parle de « password » là où les formulaires parlent de
     * « current_password », sans que la règle change.
     */
    public function verify(
        User $user,
        ?string $password,
        ?string $code,
        string $passwordField = 'current_password',
        string $codeField = 'two_factor_code',
    ): void {
        $cle = 'reauth:'.$user->getKey();

        // Un code 2FA n'a que six chiffres : sans frein, il se devine en
        // quelques minutes depuis une session volée.
        if (RateLimiter::tooManyAttempts($cle, self::MAX_ATTEMPTS)) {
            $this->journaliser($user, 'throttled');

            throw ValidationException::withMessages([
                $passwordField => __('auth.throttle', ['seconds' => RateLimiter::availableIn($cle)]),
            ]);
        }

        if ($password === null || $password === '' || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($cle, self::DECAY_SECONDS);
            $this->journaliser($user, 'password');

            throw ValidationException::withMessages([$passwordField => __('auth.password')]);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $code = $this->normaliser($code);

            if ($code === '') {
                RateLimiter::hit($cle, self::DECAY_SECONDS);
                $this->journaliser($user, 'code_missing');

                throw ValidationException::withMessages([$codeField => __('app.reauth_code_required')]);
            }

            if (! $this->codeValide($user, $code)) {
                RateLimiter::hit($cle, self::DECAY_SECONDS);
                $this->journaliser($user, 'code');

                throw ValidationException::withMessages([$codeField => __('app.reauth_code_invalid')]);
            }
        } elseif ($user->usesEmailOtp()) {
            // Le code par e-mail : même exigence, autre canal. Le code a été
            // envoyé quand le formulaire s'est ouvert (security.email-code.send).
            $code = $this->normaliser($code);

            if ($code === '') {
                RateLimiter::hit($cle, self::DECAY_SECONDS);
                $this->journaliser($user, 'email_code_missing');

                throw ValidationException::withMessages([$codeField => __('app.reauth_email_code_required')]);
            }

            if (! $this->emailOtp->verify($user, $code)) {
                RateLimiter::hit($cle, self::DECAY_SECONDS);
                $this->journaliser($user, 'email_code');

                throw ValidationException::withMessages([$codeField => __('app.reauth_email_code_invalid')]);
            }
        }

        RateLimiter::clear($cle);
    }

    /**
     * Ce que le formulaire doit demander pour cet utilisateur.
     *
     * @return array{password: bool, code: bool}
     */
    public function requirements(User $user): array
    {
        return [
            'password' => true,
            'code' => $user->secondFactor() !== null,
            'method' => $user->secondFactor(),
        ];
    }

    private function codeValide(User $user, string $code): bool
    {
        // Le fournisseur de Fortify porte la fenêtre de tolérance et la
        // protection contre le rejeu d'un code déjà utilisé.
        if ($this->provider->verify(decrypt($user->two_factor_secret), $code)) {
            return true;
        }

        // Un code de secours vaut une fois : on le retire aussitôt, comme le
        // fait la connexion.
        foreach ($user->recoveryCodes() as $codeDeSecours) {
            if (hash_equals($codeDeSecours, $code)) {
                $user->replaceRecoveryCode($codeDeSecours);

                return true;
            }
        }

        return false;
    }

    private function normaliser(?string $code): string
    {
        // Les applications affichent « 123 456 », les codes de secours
        // « abcd-efgh » : on accepte les deux tels quels.
        return trim(str_replace(' ', '', (string) $code));
    }

    private function journaliser(User $user, string $motif): void
    {
        AuditLogger::log(
            'reauthentication.failed',
            null,
            null,
            null,
            AuditLog::STATUS_FAILED,
            ['reason' => $motif, 'user_id' => $user->getKey()],
        );
    }
}
