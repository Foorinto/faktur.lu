<?php

namespace App\Console\Commands;

use App\Security\AuditChain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Où en est le journal : tête scellée, attente, dernier export, ancres, rétention. */
class AuditStatus extends Command
{
    protected $signature = 'audit:status';

    protected $description = 'État du journal d\'audit démontrable : scellement, exports, ancres, rétention';

    public function handle(AuditChain $chain): int
    {
        $tete = $chain->head();
        $attente = $chain->pending();
        $export = DB::table('audit_chain_exports')->orderByDesc('id')->first();
        $ancre = $chain->latestAnchor();

        $this->table(['Élément', 'État'], [
            ['Entrées', number_format((int) DB::table('audit_logs')->count(), 0, ',', ' ')],
            ['Tête scellée', $tete ? "#{$tete->id} ({$tete->created_at})" : 'aucune'],
            ['En attente de scellement', $attente['count'].($attente['count'] > 0 ? " (la plus ancienne : {$attente['oldest_minutes']} min)" : '')],
            ['Dernier export', $export ? sprintf('#%d à #%d le %s, %s%s', $export->from_id, $export->to_id, $export->exported_at, $export->encrypted ? 'chiffré' : 'en clair', $export->remote_path ? ' sur '.$export->remote_path : ', local seulement') : 'aucun'],
            ['Ancre de purge', $ancre ? "après #{$ancre->last_pruned_id} ({$ancre->pruned_count} entrées purgées le {$ancre->pruned_at})" : 'aucune'],
            ['Rétention', config('audit.retention_days').' jours'],
            ['Hors site', config('audit.export.cloud') ? config('backup.cloud.remote').':'.config('backup.cloud.path').'/'.config('audit.export.cloud_subdir') : 'désactivé'],
        ]);

        $this->line('Vérifier la chaîne : php artisan audit:verify');

        return self::SUCCESS;
    }
}
