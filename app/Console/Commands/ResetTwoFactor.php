<?php

namespace App\Console\Commands;

use App\Auth\EmailOtp;
use App\Auth\TrustedDevices;
use App\Models\AuditLog;
use App\Models\User;
use App\Security\TwoFactorPolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Procédure support, après vérification de l'identité du demandeur : téléphone
 * perdu sans codes de secours, ou adresse e-mail qui ne reçoit plus rien.
 *
 * Remet à zéro les deux méthodes, ferme les sessions et oublie les appareils.
 * Un compte exposé repart aussitôt avec le code par e-mail, sauf si on lui
 * laisse quelques heures (--lift-hours) pour corriger son adresse : la
 * commande quotidienne réactive le code à l'échéance.
 */
class ResetTwoFactor extends Command
{
    protected $signature = 'security:reset-two-factor
                            {email : L\'adresse du compte}
                            {--lift-hours=0 : Laisse le compte sans second facteur pendant N heures, le temps de corriger son adresse}';

    protected $description = 'Procédure support : remet à zéro le second facteur d\'un compte (application et code par e-mail), ferme ses sessions et oublie ses appareils';

    public function handle(TwoFactorPolicy $policy, TrustedDevices $appareils, EmailOtp $emailOtp): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('Aucun compte avec cette adresse.');

            return self::FAILURE;
        }

        $heures = max(0, (int) $this->option('lift-hours'));

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'email_otp_enabled_at' => null,
        ])->save();
        $emailOtp->clear($user);
        $appareils->forgetAll($user);

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->getKey())->delete();
        }

        $expose = $policy->isExposed($user);
        $etat = 'compte non exposé, aucun second facteur imposé';

        if ($expose && $heures > 0) {
            $user->forceFill([
                'two_factor_deadline_at' => now()->addHours($heures),
                'two_factor_reminded_at' => now(),
            ])->save();
            $etat = 'sans second facteur jusqu\'au '.$user->two_factor_deadline_at->format('d/m/Y H:i');
        } elseif ($expose) {
            $user->forceFill(['email_otp_enabled_at' => now()])->save();
            $etat = 'code par e-mail réactivé (compte exposé)';
        }

        AuditLog::create([
            'user_id' => $user->getKey(),
            'action' => AuditLog::ACTION_2FA_RESET_BY_SUPPORT,
            'auditable_type' => $user->getMorphClass(),
            'auditable_id' => $user->getKey(),
            'status' => AuditLog::STATUS_SUCCESS,
            'metadata' => ['lift_hours' => $heures, 'exposed' => $expose],
        ]);

        $this->info("Second facteur remis à zéro pour {$user->email} : {$etat}.");

        return self::SUCCESS;
    }
}
