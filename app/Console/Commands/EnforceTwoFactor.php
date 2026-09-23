<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Security\TwoFactorPolicy;
use Illuminate\Console\Command;

/**
 * Une fois par jour : amène les comptes exposés (IBAN enregistré et facture
 * émise) vers un second facteur. Préavis de quatorze jours, rappel deux jours
 * avant, puis le code par e-mail s'active tout seul. Voir TwoFactorPolicy.
 */
class EnforceTwoFactor extends Command
{
    protected $signature = 'security:enforce-two-factor
                            {--dry-run : Montre ce qui serait fait, sans rien écrire ni envoyer}';

    protected $description = 'Amène les comptes exposés (IBAN enregistré et facture émise) vers un second facteur : préavis, rappel, puis code par e-mail';

    private const LIBELLES = [
        TwoFactorPolicy::DEADLINE_SET => 'préavis envoyé, échéance dans 14 jours',
        TwoFactorPolicy::REMINDED => 'rappel envoyé',
        TwoFactorPolicy::ENABLED => 'code par e-mail activé',
        TwoFactorPolicy::RELEASED => 'plus exposé, échéance levée',
    ];

    public function handle(TwoFactorPolicy $policy): int
    {
        $simulation = (bool) $this->option('dry-run');

        // Le lancement se décide dans .env (SECURITY_ENFORCE_TWO_FACTOR) : le
        // code peut être déployé avant que les premiers mails ne partent. La
        // simulation reste possible pour voir la liste.
        if (! $simulation && ! config('security.enforce_two_factor')) {
            $this->warn('Parcours désactivé : SECURITY_ENFORCE_TWO_FACTOR n\'est pas à true. Rien n\'a été fait (--dry-run reste possible).');

            return self::SUCCESS;
        }
        $bilan = array_fill_keys(array_keys(self::LIBELLES), 0);
        $examines = 0;

        User::query()
            ->where(fn ($q) => $q->whereNull('is_active')->orWhere('is_active', true))
            // Un compte gelé attend d'abord la réinitialisation de son mot de passe.
            ->whereNull('security_locked_at')
            ->chunkById(200, function ($users) use ($policy, $simulation, &$bilan, &$examines) {
                foreach ($users as $user) {
                    $examines++;
                    $resultat = $policy->enforce($user, $simulation);

                    if ($resultat !== null) {
                        $bilan[$resultat]++;
                        $this->line(sprintf('  %s : %s', $user->email, self::LIBELLES[$resultat]));
                    }
                }
            });

        $this->info(sprintf(
            '%s%d comptes examinés : %d préavis, %d rappels, %d codes par e-mail activés, %d échéances levées.',
            $simulation ? '[simulation] ' : '',
            $examines,
            $bilan[TwoFactorPolicy::DEADLINE_SET],
            $bilan[TwoFactorPolicy::REMINDED],
            $bilan[TwoFactorPolicy::ENABLED],
            $bilan[TwoFactorPolicy::RELEASED],
        ));

        return self::SUCCESS;
    }
}
