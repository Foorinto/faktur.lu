<?php

namespace App\Console\Commands;

use App\Mail\AuditAlert;
use App\Security\AuditChain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Parcourt toute la chaîne du journal d'audit et dit si elle tient. Chaque
 * nuit avec --alert : une rupture ou un scellement en panne envoie un mail.
 */
class AuditVerify extends Command
{
    protected $signature = 'audit:verify {--alert : Envoie un mail si la chaîne est rompue ou si le scellement ne tourne plus}';

    protected $description = 'Vérifie la chaîne d\'empreintes du journal d\'audit et les traces d\'export';

    public function handle(AuditChain $chain): int
    {
        $resultat = $chain->verify();
        $enAttente = $chain->pending();
        $problemes = [];

        if ($resultat['ok']) {
            $this->info(sprintf('Chaîne intacte : %d entrée(s) vérifiée(s), tête #%s.', $resultat['checked'], $resultat['head_id'] ?? '-'));
        } else {
            $problemes[] = sprintf('Chaîne ROMPUE à l\'entrée #%d : %s (%d entrée(s) vérifiée(s) avant).', $resultat['break_id'], $resultat['reason'], $resultat['checked']);
        }

        if ($enAttente['oldest_minutes'] > (int) config('audit.seal_max_age_minutes', 10)) {
            $problemes[] = sprintf('%d entrée(s) non scellée(s), la plus ancienne depuis %d minutes : audit:seal ne tourne plus ?', $enAttente['count'], $enAttente['oldest_minutes']);
        } elseif ($enAttente['count'] > 0) {
            $this->line(sprintf('%d entrée(s) en attente de scellement (normal dans la minute).', $enAttente['count']));
        }

        foreach ($problemes as $probleme) {
            $this->error($probleme);
        }

        if ($problemes !== [] && $this->option('alert')) {
            $this->alerter($problemes);
        }

        return $problemes === [] ? self::SUCCESS : self::FAILURE;
    }

    private function alerter(array $problemes): void
    {
        $destinataire = config('audit.notification_email');

        if (! $destinataire) {
            return;
        }

        Mail::to($destinataire)->send(new AuditAlert(
            "JOURNAL D'AUDIT : anomalie détectée",
            $problemes,
            "À regarder sans attendre : une chaîne rompue veut dire qu'une entrée a été modifiée ou supprimée après coup, ou que la base a été restaurée.",
        ));
    }
}
