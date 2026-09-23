<?php

namespace App\Console\Commands;

use App\Mail\AuditAlert;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Chaque semaine : chaque archive est relue et son empreinte comparée à ce
 * que la facture en dit. Un fichier absent ou retouché se voit ; avec
 * --alert, un mail part.
 */
class ArchiveVerify extends Command
{
    protected $signature = 'archive:verify {--alert : Envoie un mail si une archive manque ou a changé}';

    protected $description = 'Vérifie l\'intégrité des archives PDF/A (présence et empreinte)';

    public function handle(): int
    {
        $verifiees = 0;
        $problemes = [];

        Invoice::withoutGlobalScopes()
            ->whereNotNull('archived_at')
            ->orderBy('id')
            ->chunkById(200, function ($factures) use (&$verifiees, &$problemes) {
                foreach ($factures as $facture) {
                    $chemin = Storage::disk('local')->path($facture->archive_path);

                    if (! is_file($chemin)) {
                        $problemes[] = "facture #{$facture->id} ({$facture->number}) : archive absente ({$facture->archive_path})";

                        continue;
                    }

                    if (hash_file('sha256', $chemin) !== $facture->archive_checksum) {
                        $problemes[] = "facture #{$facture->id} ({$facture->number}) : archive modifiée, empreinte différente";

                        continue;
                    }

                    $verifiees++;
                }
            });

        $nonCopiees = Invoice::withoutGlobalScopes()->whereNotNull('archived_at')->whereNull('archive_uploaded_at')->count();

        if ($problemes === []) {
            $this->info("{$verifiees} archive(s) intacte(s)".($nonCopiees > 0 ? ", {$nonCopiees} pas encore copiée(s) hors site" : '').'.');

            return self::SUCCESS;
        }

        foreach ($problemes as $probleme) {
            $this->error($probleme);
        }

        if ($this->option('alert') && config('archive.notification_email')) {
            Mail::to(config('archive.notification_email'))->send(new AuditAlert(
                'ARCHIVES PDF/A : anomalie détectée',
                array_slice($problemes, 0, 50),
                count($problemes).' archive(s) en cause. Une archive absente ou modifiée n\'est plus un exemplaire probant : à regarder sans attendre.',
            ));
        }

        return self::FAILURE;
    }
}
