<?php

namespace App\Listeners;

use App\Models\User;
use App\Security\SecurityAlerter;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;

/**
 * Ce que la 2FA laisse comme trace quand elle bouge.
 *
 * Fortify gère l'activation et la désactivation dans ses propres contrôleurs,
 * hors de portée de nos actions : on l'écoute donc. Désactiver la 2FA, c'est
 * retirer la seule barrière qui tient quand la boîte mail est compromise, et
 * c'est le geste qu'un attaquant fait en premier : le titulaire en est
 * prévenu, avec le lien de gel.
 */
class HandleTwoFactorEvents
{
    public function __construct(private SecurityAlerter $alerter) {}

    public function onConfirmed(TwoFactorAuthenticationConfirmed $event): void
    {
        AuditLogger::log2FAEnabled();
        $this->fermerLesAutresSessions($event->user);
    }

    public function onDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        AuditLogger::log2FADisabled();
        $this->fermerLesAutresSessions($event->user);

        if ($event->user instanceof User) {
            $this->alerter->alert($event->user, SecurityAlerter::TWO_FACTOR_DISABLED);
        }
    }

    /**
     * Un changement de 2FA ferme les appareils restés ouverts ailleurs, comme
     * le fait un changement de mot de passe. Activer la 2FA sur un poste sain
     * ne doit pas laisser vivre une session volée avant ; la désactiver non
     * plus. La session courante, celle qui vient de faire le geste, reste.
     */
    private function fermerLesAutresSessions(mixed $user): void
    {
        if (! $user instanceof User || config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->when(request()?->hasSession(), fn ($q) => $q->where('id', '!=', request()->session()->getId()))
            ->delete();
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(): array
    {
        return [
            TwoFactorAuthenticationConfirmed::class => 'onConfirmed',
            TwoFactorAuthenticationDisabled::class => 'onDisabled',
        ];
    }
}
