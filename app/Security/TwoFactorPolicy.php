<?php

namespace App\Security;

use App\Models\AuditLog;
use App\Models\BusinessSettings;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\TwoFactorNoticeNotification;

/**
 * Qui doit avoir un second facteur, et comment on l'y amène.
 *
 * Un compte est « exposé » quand il porte un IBAN et a émis au moins une
 * facture : c'est là qu'une prise de contrôle fait des dégâts (changer l'IBAN
 * des factures pour détourner les paiements). Chiffres de production du
 * 2026-09-22 : 23 comptes sur 55, dont 2 avec une application.
 *
 * On n'enferme personne dehors. Un compte exposé sans second facteur reçoit un
 * préavis, un rappel deux jours avant l'échéance, puis le code par e-mail
 * s'active tout seul : rien à installer, l'adresse est déjà vérifiée. Ceux qui
 * préfèrent l'application l'activent avant. Une fois exposé, on peut passer
 * d'une méthode à l'autre, pas revenir à rien.
 *
 * La commande security:enforce-two-factor fait avancer les comptes une fois
 * par jour ; ici, la règle et un pas à la fois.
 */
class TwoFactorPolicy
{
    public const GRACE_DAYS = 14;

    public const REMINDER_DAYS_BEFORE = 2;

    public const DEADLINE_SET = 'deadline_set';

    public const REMINDED = 'reminded';

    public const ENABLED = 'enabled';

    public const RELEASED = 'released';

    public function isExposed(User $user): bool
    {
        // Hors portée multi-tenant : la commande tourne sans session, et un
        // compte connecté ne doit pas lire ses propres réglages à la place
        // de ceux du compte examiné.
        $reglages = BusinessSettings::withoutGlobalScopes()
            ->where('user_id', $user->getKey())
            ->first();

        if ($reglages === null || trim((string) $reglages->iban) === '') {
            return false;
        }

        return Invoice::withoutGlobalScopes()
            ->where('user_id', $user->getKey())
            ->whereNotNull('finalized_at')
            ->exists();
    }

    public function hasSecondFactor(User $user): bool
    {
        return $user->hasEnabledTwoFactorAuthentication() || $user->usesEmailOtp();
    }

    /** L'application se désactive, sauf si c'est le dernier facteur d'un compte exposé. */
    public function canDisableApp(User $user): bool
    {
        return $user->usesEmailOtp() || ! $this->isExposed($user);
    }

    public function canDisableEmail(User $user): bool
    {
        return $user->hasEnabledTwoFactorAuthentication() || ! $this->isExposed($user);
    }

    /**
     * Ce que le bandeau doit dire, ou null. Le premier test ne coûte aucune
     * requête : la plupart des comptes n'ont pas d'échéance.
     *
     * @return array{deadline: string, days_left: int}|null
     */
    public function notice(User $user): ?array
    {
        if ($user->two_factor_deadline_at === null || $this->hasSecondFactor($user) || ! $this->isExposed($user)) {
            return null;
        }

        $echeance = $user->two_factor_deadline_at->copy()->startOfDay();

        return [
            'deadline' => $user->two_factor_deadline_at->toIso8601String(),
            'days_left' => max(0, (int) now()->startOfDay()->diffInDays($echeance, false)),
        ];
    }

    /**
     * Fait avancer un compte d'un pas. Renvoie ce qui a été fait, ou null.
     * En simulation, dit ce qui serait fait sans rien écrire ni envoyer.
     */
    public function enforce(User $user, bool $dryRun = false): ?string
    {
        $expose = $this->isExposed($user);
        $facteur = $this->hasSecondFactor($user);

        if (! $expose) {
            // Plus exposé (IBAN retiré) : l'échéance tombe, et un retour à
            // l'exposition rouvrira un préavis complet.
            if ($user->two_factor_deadline_at !== null && ! $facteur) {
                if (! $dryRun) {
                    $user->forceFill(['two_factor_deadline_at' => null, 'two_factor_reminded_at' => null])->save();
                    $this->journaliser($user, AuditLog::ACTION_2FA_RELEASED);
                }

                return self::RELEASED;
            }

            return null;
        }

        if ($facteur) {
            return null;
        }

        if ($user->two_factor_deadline_at === null) {
            if (! $dryRun) {
                $user->forceFill([
                    'two_factor_deadline_at' => now()->addDays(self::GRACE_DAYS),
                    'two_factor_reminded_at' => null,
                ])->save();
                $this->prevenir($user, TwoFactorNoticeNotification::DEADLINE);
                $this->journaliser($user, AuditLog::ACTION_2FA_DEADLINE_SET, [
                    'deadline' => $user->two_factor_deadline_at->toIso8601String(),
                ]);
            }

            return self::DEADLINE_SET;
        }

        if ($user->two_factor_deadline_at->lte(now())) {
            if (! $dryRun) {
                // Le mail d'abord : activer un code par e-mail à quelqu'un dont
                // la boîte ne reçoit rien l'enfermerait dehors. Si le mail ne
                // part pas, on réessaie demain.
                if (! $this->prevenir($user, TwoFactorNoticeNotification::ENABLED)) {
                    return null;
                }
                $user->forceFill(['email_otp_enabled_at' => now()])->save();
                $this->journaliser($user, AuditLog::ACTION_EMAIL_OTP_AUTO_ENABLED);
            }

            return self::ENABLED;
        }

        if ($user->two_factor_reminded_at === null
            && $user->two_factor_deadline_at->lte(now()->addDays(self::REMINDER_DAYS_BEFORE))) {
            if (! $dryRun) {
                $user->forceFill(['two_factor_reminded_at' => now()])->save();
                $this->prevenir($user, TwoFactorNoticeNotification::REMINDER);
                $this->journaliser($user, AuditLog::ACTION_2FA_REMINDER_SENT);
            }

            return self::REMINDED;
        }

        return null;
    }

    private function prevenir(User $user, string $evenement): bool
    {
        try {
            $user->notify(new TwoFactorNoticeNotification($evenement, $user->two_factor_deadline_at, $user->locale ?? 'fr'));

            return true;
        } catch (\Throwable $e) {
            // Le préavis et le rappel avancent quand même : le bandeau dit la
            // même chose à l'écran.
            report($e);

            return false;
        }
    }

    private function journaliser(User $user, string $action, array $metadata = []): void
    {
        // Pas AuditLogger::log : hors session, il n'aurait pas d'auteur, et
        // l'entrée doit porter le compte concerné.
        AuditLog::create([
            'user_id' => $user->getKey(),
            'action' => $action,
            'auditable_type' => $user->getMorphClass(),
            'auditable_id' => $user->getKey(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'status' => AuditLog::STATUS_SUCCESS,
            'metadata' => $metadata ?: null,
        ]);
    }
}
