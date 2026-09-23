<?php

namespace App\Console\Commands;

use App\Mail\AuditAlert;
use App\Security\AuditExporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** Chaque nuit, après la sauvegarde : la copie signée du journal part hors site. */
class AuditExport extends Command
{
    protected $signature = 'audit:export {--alert : Envoie un mail si l\'export échoue}';

    protected $description = 'Exporte les entrées scellées depuis le dernier export (fichier signé, chiffré, déposé hors site)';

    public function handle(AuditExporter $exporter): int
    {
        if (! config('audit.export.enabled')) {
            $this->warn('Export désactivé (AUDIT_EXPORT_ENABLED).');

            return self::SUCCESS;
        }

        try {
            $resultat = $exporter->run();
        } catch (\Throwable $e) {
            Log::error('[Audit] Export en échec : '.$e->getMessage());
            $this->error('Export en échec : '.$e->getMessage());

            if ($this->option('alert') && config('audit.notification_email')) {
                Mail::to(config('audit.notification_email'))->send(new AuditAlert(
                    "JOURNAL D'AUDIT : export hors site en échec",
                    [$e->getMessage()],
                    "Les entrées restent en base, scellées ; l'export reprendra au prochain passage.",
                ));
            }

            return self::FAILURE;
        }

        if ($resultat === null) {
            $this->line('Rien de nouveau à exporter.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%d entrée(s) exportée(s) (#%d à #%d) : %s%s%s.',
            $resultat['entries'],
            $resultat['from_id'],
            $resultat['to_id'],
            basename($resultat['file']),
            $resultat['encrypted'] ? ', chiffré' : ', EN CLAIR',
            $resultat['remote'] ? ', déposé sur '.$resultat['remote'] : ', local seulement',
        ));

        return self::SUCCESS;
    }
}
