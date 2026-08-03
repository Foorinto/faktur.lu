<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * Diagnostic de la chaîne de sauvegarde, en lecture seule.
 *
 * Répond à une seule question : « une copie de la base existe-t-elle ailleurs
 * que sur ce serveur, et de quand date-t-elle ? » Le besoin est né d'un cas
 * réel : les dumps locaux se créaient chaque nuit pendant que l'envoi vers
 * pCloud échouait en silence, l'erreur ne vivant que dans le fichier de log.
 */
class BackupCheckCommand extends Command
{
    protected $signature = 'backup:check';

    protected $description = 'Diagnostique la sauvegarde : planification, dumps locaux, rclone, dépôt distant';

    public function handle(): int
    {
        $problems = [];

        $this->line('');
        $this->info('── Configuration ──');
        $this->line('  BACKUP_ENABLED        : '.($this->yn(config('backup.enabled'))));
        $this->line('  Heure planifiée       : '.config('backup.schedule_time'));
        $this->line('  Chiffrement           : '.$this->yn((bool) config('backup.encryption_key')));
        $this->line('  BACKUP_CLOUD_ENABLED  : '.$this->yn(config('backup.cloud.enabled')));
        $this->line('  Remote rclone         : '.config('backup.cloud.remote').':'.config('backup.cloud.path'));
        $this->line('  Email d\'alerte        : '.(config('backup.notification_email') ?: '— aucun —'));

        if (! config('backup.enabled')) {
            $problems[] = 'BACKUP_ENABLED est à false : aucune sauvegarde n\'est planifiée.';
        }

        if (! config('backup.notification_email')) {
            $problems[] = 'Aucun email d\'alerte : un échec resterait invisible.';
        }

        // --- Dumps locaux ---
        $this->line('');
        $this->info('── Sauvegardes locales ──');
        $path = config('backup.local.path');
        $files = is_dir($path) ? glob($path.'/backup_*') : [];

        if (empty($files)) {
            $this->line('  Aucune sauvegarde locale trouvée dans '.$path);
            $problems[] = 'Aucun dump local : le planificateur ne tourne probablement pas '
                .'(vérifier que le cron appelle « php artisan schedule:run » chaque minute).';
        } else {
            usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));
            $last = $files[0];
            // abs() : selon la version de Carbon, diffInHours est signé — un
            // âge négatif ferait passer le contrôle « plus de 48 h » à côté.
            $age = (int) abs(now()->diffInHours(\Carbon\Carbon::createFromTimestamp(filemtime($last))));

            $this->line('  Nombre de fichiers    : '.count($files));
            $this->line('  Plus récent           : '.basename($last));
            $this->line('  Date                  : '.date('Y-m-d H:i', filemtime($last))." ({$age} h)");

            if ($age > 48) {
                $problems[] = "Le dernier dump local date de {$age} heures : la planification est en panne.";
            }
        }

        // --- Chaîne distante ---
        if (! config('backup.cloud.enabled')) {
            $this->line('');
            $this->warn('── Copie hors-site : DÉSACTIVÉE ──');
            $problems[] = 'BACKUP_CLOUD_ENABLED est à false : rien n\'est envoyé sur pCloud. '
                .'Les dumps ne vivent que sur le serveur qu\'ils protègent.';

            return $this->verdict($problems);
        }

        $this->line('');
        $this->info('── Copie hors-site ──');
        $binary = config('backup.cloud.binary', 'rclone');

        $version = Process::timeout(30)->run(escapeshellarg($binary).' version');

        if (! $version->successful()) {
            $this->line('  rclone                : INTROUVABLE ('.$binary.')');
            $problems[] = 'Le binaire rclone est introuvable depuis PHP. Sur un mutualisé il vit '
                .'souvent dans ~/bin, absent du PATH du cron : renseigner BACKUP_RCLONE_BINARY '
                .'avec le chemin absolu.';

            return $this->verdict($problems);
        }

        $this->line('  rclone                : '.strtok(trim($version->output()), "\n"));

        $remote = config('backup.cloud.remote');
        $remotes = Process::timeout(30)->run(escapeshellarg($binary).' listremotes');

        if (! str_contains($remotes->output(), $remote.':')) {
            $this->line('  Remote « '.$remote.' »      : NON CONFIGURÉ');
            $this->line('  Remotes connus        : '.(trim($remotes->output()) ?: '— aucun —'));
            $problems[] = "Le remote « {$remote} » n'existe pas dans la configuration rclone de "
                .'ce compte système. Lancer « rclone config » sous le même utilisateur que le cron.';

            return $this->verdict($problems);
        }

        $destination = $remote.':'.config('backup.cloud.path');
        $listing = Process::timeout(120)->run(
            sprintf('%s lsl %s --include "backup_*"', escapeshellarg($binary), escapeshellarg($destination))
        );

        if (! $listing->successful()) {
            $this->line('  Dépôt distant         : INACCESSIBLE');
            $problems[] = 'Le dépôt distant est injoignable : '.trim($listing->errorOutput());

            return $this->verdict($problems);
        }

        $lines = array_filter(explode("\n", trim($listing->output())));

        $this->line('  Fichiers sur '.$destination.' : '.count($lines));

        if (empty($lines)) {
            $problems[] = 'Le remote répond mais ne contient aucune sauvegarde. '
                .'Lancer « php artisan backup:run » à la main pour voir l\'erreur réelle.';
        } else {
            foreach (array_slice($lines, -3) as $line) {
                $this->line('    '.trim($line));
            }
        }

        return $this->verdict($problems);
    }

    private function yn(bool $value): string
    {
        return $value ? 'oui' : 'NON';
    }

    private function verdict(array $problems): int
    {
        $this->line('');

        if (empty($problems)) {
            $this->info('✓ La chaîne de sauvegarde est complète : dump local et copie hors-site.');

            return self::SUCCESS;
        }

        $this->error('Problèmes détectés :');
        foreach ($problems as $i => $problem) {
            $this->line('  '.($i + 1).'. '.$problem);
        }

        return self::FAILURE;
    }
}
