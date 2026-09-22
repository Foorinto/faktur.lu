<?php

namespace App\Auth;

use App\Models\User;
use App\Notifications\EmailOtpNotification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Le second facteur par e-mail : un code à six chiffres envoyé à l'adresse du
 * compte, valable dix minutes, cinq essais, à usage unique.
 *
 * Plus faible qu'une application d'authentification (le code voyage par une
 * boîte mail, qui est déjà la clé du compte via « mot de passe oublié »), mais
 * il n'y a rien à installer : c'est ce qui permet de l'imposer aux comptes
 * exposés sans enfermer personne dehors (voir App\Security\TwoFactorPolicy).
 *
 * Le code n'est stocké que haché. Un seul code actif par compte : en demander
 * un nouveau annule le précédent. Le mail part tout de suite, sans file
 * d'attente : quelqu'un attend devant l'écran.
 */
class EmailOtp
{
    public const VALIDITY_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    /** Délai minimal entre deux envois. */
    public const RESEND_SECONDS = 30;

    /** Envois au plus par quart d'heure, tous motifs confondus. */
    public const MAX_SENDS = 5;

    public const SENDS_WINDOW_SECONDS = 900;

    /**
     * Génère et envoie un code. Lève une ValidationException sur le champ
     * donné si c'est trop tôt, trop souvent, ou si le mail ne part pas.
     */
    public function send(User $user, string $field = 'code'): void
    {
        $cle = 'email-otp-send:'.$user->getKey();

        if (RateLimiter::tooManyAttempts($cle, self::MAX_SENDS)) {
            throw ValidationException::withMessages([
                $field => __('app.email_otp.too_many_sends', ['seconds' => RateLimiter::availableIn($cle)]),
            ]);
        }

        $attente = $this->secondsBeforeResend($user);

        if ($attente > 0) {
            throw ValidationException::withMessages([
                $field => __('app.email_otp.wait_before_resend', ['seconds' => $attente]),
            ]);
        }

        RateLimiter::hit($cle, self::SENDS_WINDOW_SECONDS);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'email_otp_code' => self::hash($code),
            'email_otp_expires_at' => now()->addMinutes(self::VALIDITY_MINUTES),
            'email_otp_attempts' => 0,
            'email_otp_sent_at' => now(),
        ])->save();

        try {
            $user->notify(new EmailOtpNotification($code, $user->locale ?? 'fr'));
        } catch (\Throwable $e) {
            report($e);
            // Sans mail, pas de code : on ne laisse pas un code fantôme.
            $this->clear($user);

            throw ValidationException::withMessages([$field => __('app.email_otp.send_failed')]);
        }
    }

    public function secondsBeforeResend(User $user): int
    {
        $dernier = $user->email_otp_sent_at;

        if ($dernier === null) {
            return 0;
        }

        return max(0, self::RESEND_SECONDS - (int) $dernier->diffInSeconds(now()));
    }

    /** Vrai si le code est le bon ; il est alors consommé. */
    public function verify(User $user, ?string $code): bool
    {
        // « 123 456 » tel qu'on le recopie : on ne garde que les chiffres.
        $code = preg_replace('/\D/', '', (string) $code);

        if ($code === ''
            || $user->email_otp_code === null
            || $user->email_otp_expires_at === null
            || $user->email_otp_expires_at->isPast()
            || $user->email_otp_attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! hash_equals($user->email_otp_code, self::hash($code))) {
            $user->forceFill(['email_otp_attempts' => $user->email_otp_attempts + 1])->save();

            return false;
        }

        $this->clear($user);

        return true;
    }

    public function clear(User $user): void
    {
        $user->forceFill([
            'email_otp_code' => null,
            'email_otp_expires_at' => null,
            'email_otp_attempts' => 0,
        ])->save();
    }

    private static function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
