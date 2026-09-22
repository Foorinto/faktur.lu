<?php

namespace App\Security;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\SecurityAlertNotification;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Alertes de sécurité : prévenir le titulaire qu'un geste sensible vient
 * d'être fait sur son compte, et lui donner un moyen de le contester.
 *
 * Chaque alerte porte un lien « ce n'était pas moi », signé et daté, qui gèle
 * le compte : sessions fermées, connexion refusée jusqu'à réinitialisation du
 * mot de passe. Le gel ne fait que refuser, jamais modifier : c'est ce qui
 * permet de l'offrir sans autre vérification que la possession du lien.
 *
 * ⚠️ Qui reçoit l'alerte. L'adresse du compte, toujours. Et l'adresse
 * précédente si elle a changé depuis moins de trente jours : quand une prise
 * de contrôle commence par changer l'email, l'ancienne boîte est le seul
 * canal qui reste au vrai titulaire. Un changement d'IBAN qui suit de près
 * un changement d'adresse est exactement le scénario redouté.
 */
class SecurityAlerter
{
    public const IBAN_CHANGED = 'iban_changed';

    public const PAYMENT_QRCODE_CHANGED = 'payment_qrcode_changed';

    public const EMAIL_CHANGED = 'email_changed';

    public const PASSWORD_CHANGED = 'password_changed';

    public const TWO_FACTOR_DISABLED = 'two_factor_disabled';

    public const EVENTS = [
        self::IBAN_CHANGED,
        self::PAYMENT_QRCODE_CHANGED,
        self::EMAIL_CHANGED,
        self::PASSWORD_CHANGED,
        self::TWO_FACTOR_DISABLED,
    ];

    /** Fenêtre pendant laquelle l'ancienne adresse reste prévenue. */
    public const PREVIOUS_EMAIL_WINDOW_DAYS = 30;

    /** Durée de validité du lien de gel. */
    public const LINK_VALIDITY_DAYS = 7;

    /**
     * @param  array<string, string>  $details  ce qui a changé, déjà masqué si sensible
     * @param  array<int, string>  $alsoTo  adresses supplémentaires, l'ancienne adresse par exemple
     */
    public function alert(User $user, string $event, array $details = [], array $alsoTo = []): void
    {
        $destinataires = collect([$user->email])
            ->merge($alsoTo)
            ->when($this->previousEmailStillRelevant($user), fn ($c) => $c->push($user->previous_email))
            ->filter()
            ->map(fn ($e) => mb_strtolower(trim($e)))
            ->unique()
            ->values();

        $lien = URL::temporarySignedRoute(
            'security.not-me',
            now()->addDays(self::LINK_VALIDITY_DAYS),
            ['user' => $user->getKey(), 'event' => $event],
        );

        $notification = new SecurityAlertNotification(
            $event,
            $details,
            $lien,
            $user->locale ?? 'fr',
            request()?->ip(),
        );

        // ⚠️ L'envoi est synchrone et le geste est déjà enregistré quand on
        // arrive ici. Un serveur de mail en panne ne doit pas transformer un
        // changement d'IBAN réussi en page d'erreur : l'échec est signalé
        // dans les journaux et dans l'audit, jamais à la place du résultat.
        $echecs = 0;

        foreach ($destinataires as $adresse) {
            try {
                Notification::route('mail', $adresse)->notify($notification);
            } catch (\Throwable $e) {
                $echecs++;
                report($e);
            }
        }

        AuditLogger::log(
            'security.alert_sent',
            $user,
            null,
            null,
            $echecs === 0 ? AuditLog::STATUS_SUCCESS : AuditLog::STATUS_FAILED,
            ['event' => $event, 'recipients' => $destinataires->count(), 'failed' => $echecs],
        );
    }

    /**
     * « LU28 **** **** **** 0000 » : assez pour reconnaître son compte, pas
     * assez pour le recopier. Le mail traverse des boîtes qu'on ne contrôle
     * pas.
     */
    public static function maskIban(?string $iban): string
    {
        $propre = strtoupper((string) preg_replace('/\s+/', '', (string) $iban));

        if (strlen($propre) < 8) {
            return $propre === '' ? '' : str_repeat('*', strlen($propre));
        }

        $masque = substr($propre, 0, 4).str_repeat('*', strlen($propre) - 8).substr($propre, -4);

        return trim(chunk_split($masque, 4, ' '));
    }

    private function previousEmailStillRelevant(User $user): bool
    {
        return $user->previous_email !== null
            && $user->email_changed_at !== null
            && $user->email_changed_at->greaterThan(now()->subDays(self::PREVIOUS_EMAIL_WINDOW_DAYS));
    }
}
