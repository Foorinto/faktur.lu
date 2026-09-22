<?php

namespace App\Listeners;

use App\Models\User;
use App\Security\SecurityAlerter;
use App\Services\AuditLogger;
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
    }

    public function onDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        AuditLogger::log2FADisabled();

        if ($event->user instanceof User) {
            $this->alerter->alert($event->user, SecurityAlerter::TWO_FACTOR_DISABLED);
        }
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
