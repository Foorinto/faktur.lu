<?php

namespace App\Console\Commands;

use App\Mail\AuditAlert;
use App\Models\Invoice;
use App\Services\PdfArchiveService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Chaque nuit : archive les factures finalisées qui n'ont pas encore
 * d'archive (l'existant au déploiement, ou un échec à la finalisation).
 * Borné par passage : Ghostscript facture par facture prend une seconde.
 */
class ArchiveCatchUp extends Command
{
    protected $signature = 'archive:catch-up
                            {--limit= : Factures au plus par passage (défaut : config archive.catch_up.limit)}
                            {--seconds= : Budget de temps en secondes (défaut : config archive.catch_up.seconds)}
                            {--alert : Envoie un mail si des factures ne s\'archivent pas}';

    protected $description = 'Archive en PDF/A les factures finalisées qui n\'ont pas encore d\'archive';

    public function handle(PdfArchiveService $archives): int
    {
        $limite = (int) ($this->option('limit') ?: config('archive.catch_up.limit', 200));
        $budget = (int) ($this->option('seconds') ?: config('archive.catch_up.seconds', 300));
        $debut = microtime(true);
        $faites = 0;
        $ratees = 0;
        $enEchec = [];

        $enAttente = Invoice::withoutGlobalScopes()
            ->whereNull('archived_at')
            ->whereIn('status', [Invoice::STATUS_FINALIZED, Invoice::STATUS_SENT, Invoice::STATUS_PAID])
            ->orderBy('id')
            ->limit($limite)
            ->get();

        foreach ($enAttente as $facture) {
            if (microtime(true) - $debut > $budget) {
                $this->warn("Budget de {$budget} s épuisé, on reprend demain.");

                break;
            }

            if ($archives->archiveQuietly($facture)) {
                $faites++;
            } else {
                $ratees++;
                $enEchec[] = "facture #{$facture->id} ({$facture->number})";
            }
        }

        $restantes = Invoice::withoutGlobalScopes()
            ->whereNull('archived_at')
            ->whereIn('status', [Invoice::STATUS_FINALIZED, Invoice::STATUS_SENT, Invoice::STATUS_PAID])
            ->count();

        $this->info("{$faites} facture(s) archivée(s), {$ratees} en échec, {$restantes} restante(s).");

        // Une facture qui ne s'archive pas reviendra chaque nuit : il faut le
        // savoir, sinon l'échec silencieux dure des mois.
        if ($ratees > 0 && $this->option('alert') && config('archive.notification_email')) {
            Mail::to(config('archive.notification_email'))->send(new AuditAlert(
                'ARCHIVES PDF/A : des factures ne s\'archivent pas',
                array_slice($enEchec, 0, 50),
                "{$ratees} facture(s) sans archive après le rattrapage de cette nuit. Le détail de chaque échec est dans le journal du serveur (« Archivage impossible »).",
            ));
        }

        return $ratees > 0 ? self::FAILURE : self::SUCCESS;
    }
}
