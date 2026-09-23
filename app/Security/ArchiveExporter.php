<?php

namespace App\Security;

use App\Models\Invoice;
use App\Services\BackupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * La copie hors site des archives PDF/A (FEAT-126).
 *
 * Chaque nuit, les archives pas encore parties sont vérifiées (empreinte),
 * chiffrées une à une avec la clé des sauvegardes, déposées d'un seul
 * `rclone copy` dans le sous-dossier `archives` du dépôt, relues sur le
 * distant, puis marquées. Jamais en clair : ce sont des factures nominatives.
 * Le chemin distant reprend celui du disque (compte/année/mois/numéro.pdf.enc),
 * pour qu'un exemplaire se retrouve sans la base.
 */
class ArchiveExporter
{
    public function __construct(private BackupService $backups) {}

    /**
     * @return array{uploaded: int, missing: list<int>, corrupt: list<int>, pending: int}
     */
    public function run(int $limit = 500): array
    {
        $bilan = ['uploaded' => 0, 'missing' => [], 'corrupt' => [], 'pending' => 0];

        if (! config('archive.export.cloud')) {
            return $bilan;
        }

        if (! config('backup.encryption_key')) {
            throw new RuntimeException('Copie hors site refusée : pas de clé de chiffrement (BACKUP_ENCRYPTION_KEY). Les archives sont des factures nominatives.');
        }

        $factures = Invoice::withoutGlobalScopes()
            ->whereNotNull('archived_at')
            ->whereNull('archive_uploaded_at')
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'archive_path', 'archive_checksum']);

        if ($factures->isEmpty()) {
            return $bilan;
        }

        $scene = storage_path('app/temp/archive-export-'.uniqid());
        File::ensureDirectoryExists($scene, 0700);

        $prepares = [];

        try {
            foreach ($factures as $facture) {
                $local = Storage::disk('local')->path($facture->archive_path);

                if (! is_file($local)) {
                    $bilan['missing'][] = (int) $facture->id;

                    continue;
                }

                // Rien ne part si le fichier ne correspond plus à son empreinte.
                if (hash_file('sha256', $local) !== $facture->archive_checksum) {
                    $bilan['corrupt'][] = (int) $facture->id;

                    continue;
                }

                $relatif = preg_replace('#^archive/#', '', $facture->archive_path);
                $copie = $scene.'/'.$relatif;
                File::ensureDirectoryExists(dirname($copie), 0700);
                copy($local, $copie);
                $chiffre = $this->backups->encrypt($copie);
                unlink($copie);
                $prepares[(int) $facture->id] = $relatif.'.enc';
            }

            if ($prepares !== []) {
                $destination = $this->destination();
                $this->envoyer($scene, $destination);
                $presents = $this->presents($destination);

                foreach ($prepares as $id => $relatif) {
                    if (in_array($relatif, $presents, true)) {
                        DB::table('invoices')->where('id', $id)->update([
                            'archive_uploaded_at' => now(),
                            'archive_remote_path' => $destination.'/'.$relatif,
                        ]);
                        $bilan['uploaded']++;
                    }
                }

                $bilan['pending'] = count($prepares) - $bilan['uploaded'];
            }
        } finally {
            File::deleteDirectory($scene);
        }

        return $bilan;
    }

    public function destination(): string
    {
        return config('backup.cloud.remote').':'.rtrim((string) config('backup.cloud.path'), '/').'/'.trim((string) config('archive.export.cloud_subdir', 'archives'), '/');
    }

    private function envoyer(string $scene, string $destination): void
    {
        $process = Process::timeout(900)->run(sprintf(
            '%s copy %s %s',
            escapeshellarg((string) config('backup.cloud.binary', 'rclone')),
            escapeshellarg($scene),
            escapeshellarg($destination),
        ));

        if (! $process->successful()) {
            throw new RuntimeException('rclone copy en échec : '.$process->errorOutput());
        }
    }

    /**
     * Ce que le distant contient, relu plusieurs fois : pCloud n'indexe pas
     * instantanément ce qu'il vient de recevoir (même précaution que la
     * sauvegarde).
     *
     * @return list<string>
     */
    private function presents(string $destination): array
    {
        $delais = config('backup.cloud.verify_delays', [2, 5, 10, 20]);
        $liste = [];

        for ($i = 0; $i <= count($delais); $i++) {
            if ($i > 0) {
                sleep($delais[$i - 1]);
            }

            $process = Process::timeout(300)->run(sprintf(
                '%s lsf -R --files-only %s',
                escapeshellarg((string) config('backup.cloud.binary', 'rclone')),
                escapeshellarg($destination),
            ));

            if ($process->successful()) {
                $liste = array_values(array_filter(array_map('trim', explode("\n", $process->output()))));

                if ($liste !== []) {
                    return $liste;
                }
            }
        }

        return $liste;
    }
}
