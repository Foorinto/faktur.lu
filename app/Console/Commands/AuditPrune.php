<?php

namespace App\Console\Commands;

use App\Security\AuditChain;
use Illuminate\Console\Command;

/**
 * La rétention du journal : au-delà de cinq ans, les entrées scellées et
 * exportées sont supprimées, et une ancre garde la chaîne vérifiable.
 */
class AuditPrune extends Command
{
    protected $signature = 'audit:prune {--days= : Rétention en jours (défaut : config audit.retention_days)}';

    protected $description = 'Supprime les entrées du journal d\'audit plus anciennes que la rétention, si elles sont scellées et exportées';

    public function handle(AuditChain $chain): int
    {
        $jours = (int) ($this->option('days') ?: config('audit.retention_days', 1825));

        if ($jours < 365) {
            $this->error('Rétention refusée en dessous d\'un an.');

            return self::FAILURE;
        }

        $resultat = $chain->prune($jours);

        if ($resultat['deleted'] === 0) {
            $this->line("Rien à purger (rétention {$jours} jours).");
        } else {
            $this->info(sprintf('%d entrée(s) supprimée(s), ancre #%d posée.', $resultat['deleted'], $resultat['anchor_id']));
        }

        return self::SUCCESS;
    }
}
