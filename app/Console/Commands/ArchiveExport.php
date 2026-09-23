<?php

namespace App\Console\Commands;

use App\Mail\AuditAlert;
use App\Security\ArchiveExporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** Chaque nuit, après la sauvegarde : les archives pas encore parties sont copiées hors site, chiffrées. */
class ArchiveExport extends Command
{
    protected $signature = 'archive:export {--alert : Envoie un mail si la copie échoue} {--limit= : Archives au plus par passage}';

    protected $description = 'Copie hors site (chiffrée, via rclone) les archives PDF/A qui n\'y sont pas encore';

    public function handle(ArchiveExporter $exporter): int
    {
        if (! config('archive.export.cloud')) {
            $this->warn('Copie hors site désactivée (ARCHIVE_EXPORT_CLOUD / BACKUP_CLOUD_ENABLED).');

            return self::SUCCESS;
        }

        try {
            $bilan = $exporter->run((int) ($this->option('limit') ?: config('archive.export.limit', 500)));
        } catch (\Throwable $e) {
            Log::error('[Archive] Copie hors site en échec : '.$e->getMessage());
            $this->error('Copie hors site en échec : '.$e->getMessage());

            if ($this->option('alert') && config('archive.notification_email')) {
                Mail::to(config('archive.notification_email'))->send(new AuditAlert(
                    'ARCHIVES PDF/A : copie hors site en échec',
                    [$e->getMessage()],
                    'Les archives restent sur le serveur ; la copie reprendra au prochain passage.',
                ));
            }

            return self::FAILURE;
        }

        $this->info(sprintf(
            '%d archive(s) copiée(s) hors site%s%s%s.',
            $bilan['uploaded'],
            $bilan['pending'] > 0 ? ", {$bilan['pending']} envoyée(s) mais pas encore visible(s) sur le distant (reprise demain)" : '',
            $bilan['missing'] !== [] ? ', fichiers ABSENTS pour les factures #'.implode(', #', $bilan['missing']) : '',
            $bilan['corrupt'] !== [] ? ', empreinte DIFFÉRENTE pour les factures #'.implode(', #', $bilan['corrupt']) : '',
        ));

        if (($bilan['missing'] !== [] || $bilan['corrupt'] !== []) && $this->option('alert') && config('archive.notification_email')) {
            Mail::to(config('archive.notification_email'))->send(new AuditAlert(
                'ARCHIVES PDF/A : anomalie avant copie hors site',
                array_merge(
                    array_map(fn ($id) => "facture #{$id} : archive absente", $bilan['missing']),
                    array_map(fn ($id) => "facture #{$id} : empreinte différente, rien n'est parti", $bilan['corrupt']),
                ),
                'archive:verify donne le détail.',
            ));
        }

        return self::SUCCESS;
    }
}
