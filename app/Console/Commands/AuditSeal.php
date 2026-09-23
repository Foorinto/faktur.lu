<?php

namespace App\Console\Commands;

use App\Security\AuditChain;
use Illuminate\Console\Command;

/** Chaque minute : scelle les entrées du journal d'audit qui ne le sont pas encore. */
class AuditSeal extends Command
{
    protected $signature = 'audit:seal';

    protected $description = 'Scelle les entrées du journal d\'audit non encore scellées (chaîne d\'empreintes)';

    public function handle(AuditChain $chain): int
    {
        $scellees = $chain->sealPending();

        if ($scellees > 0) {
            $this->info("{$scellees} entrée(s) scellée(s), tête #{$chain->head()?->id}.");
        }

        return self::SUCCESS;
    }
}
