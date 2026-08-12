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

        // --- Planification ---
        //
        // Un dump absent ressemble à un envoi raté vu de l'extérieur. La
        // distinction se fait ici : si le planificateur ne tourne pas, rien
        // n'est jamais tenté et tous les autres contrôles sont trompeurs.
        $this->line('');
        $this->info('── Planification ──');

        $crontab = Process::timeout(30)->run('crontab -l');

        if (! $crontab->successful()) {
            $this->line('  crontab               : illisible depuis PHP');
        } else {
            $entries = array_values(array_filter(
                explode("\n", $crontab->output()),
                fn ($l) => str_contains($l, 'schedule:run') && ! str_starts_with(trim($l), '#')
            ));

            if (empty($entries)) {
                $this->line('  Entrée schedule:run   : ABSENTE');
                $problems[] = 'Aucune ligne « php artisan schedule:run » active dans la crontab : '
                    .'Laravel ne déclenche donc aucune tâche planifiée, sauvegarde comprise. '
                    .'La ligne attendue tourne chaque minute.';
            } else {
                foreach ($entries as $entry) {
                    $this->line('  Entrée schedule:run   : '.trim($entry));
                }
            }
        }

        $logPath = storage_path('logs/laravel.log');

        if (is_readable($logPath)) {
            $tail = Process::timeout(30)->run(
                sprintf('grep -F "[Backup]" %s | tail -n 3', escapeshellarg($logPath))
            );
            $lines = array_filter(explode("\n", trim($tail->output())));

            if (! empty($lines)) {
                $this->line('  Dernières traces      :');
                foreach ($lines as $line) {
                    $this->line('    '.mb_substr(trim($line), 0, 160));
                }
            } else {
                $this->line('  Dernières traces      : — aucune trace [Backup] dans le journal —');
            }

            if (empty($lines)) {
                $problems[] = 'Aucune trace « [Backup] » dans storage/logs/laravel.log : '
                    .'la commande backup:run n\'a jamais été exécutée par le planificateur.';
            }
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
            // « Il y a des fichiers » ne dit rien de la fraîcheur. Un dépôt qui
            // a cessé d'être alimenté il y a trois semaines contient toujours
            // des fichiers, et passait donc pour sain : c'est précisément la
            // panne qu'on veut voir, puisqu'elle ne se signale pas autrement.
            $plusRecent = $this->plusRecenteSauvegarde($lines);

            if ($plusRecent) {
                $ageHeures = (int) round((time() - $plusRecent) / 3600);
                $this->line('  Plus récente distante : '.date('Y-m-d H:i', $plusRecent)." ({$ageHeures} h)");

                // Deux jours : une nuit d'écart peut venir d'un décalage
                // horaire ou d'un cron passé juste avant la lecture. Au-delà,
                // c'est qu'une nuit entière a été manquée.
                if ($ageHeures > 48) {
                    $problems[] = "La copie hors-site la plus récente date de {$ageHeures} h. "
                        .'Au moins une nuit n\'a pas été déposée : lancer « php artisan backup:run » '
                        .'à la main pour voir l\'erreur réelle.';
                }
            }

            foreach (array_slice($lines, -3) as $line) {
                $this->line('    '.trim($line));
            }

            // Une copie du .env qu'on ne voit pas est une copie qu'on n'a pas :
            // c'est elle qui porte APP_KEY, sans laquelle les colonnes
            // chiffrées ne se relisent plus après restauration.
            $copiesEnv = array_filter($lines, fn ($l) => str_contains($l, '.env'));

            $this->line('  Copie du .env         : '.(
                $copiesEnv ? count($copiesEnv).' présente(s)' : 'AUCUNE'
            ));

            if (! $copiesEnv && config('backup.include_env', true)) {
                $problems[] = 'Aucune copie du .env sur le dépôt distant. Sans APP_KEY, une '
                    .'restauration rendrait les IBAN et la configuration de messagerie '
                    .'définitivement illisibles. Vérifier que BACKUP_ENCRYPTION_KEY est bien '
                    .'renseignée : sans elle, la copie est volontairement omise.';
            }
        }

        return $this->verdict($problems);
    }

    /**
     * Horodatage de la sauvegarde distante la plus récente.
     *
     * `rclone lsl` produit « taille AAAA-MM-JJ HH:MM:SS.nnnnnnnnn nom ». Les
     * lignes sont lues sans se fier à leur ordre : rclone ne garantit pas un
     * classement chronologique, et le dépôt peut mêler d'anciens dépôts.
     *
     * @param  list<string>  $lines
     */
    private function plusRecenteSauvegarde(array $lines): ?int
    {
        $horodatages = [];

        foreach ($lines as $line) {
            if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line, $m)) {
                $t = strtotime($m[1]);
                if ($t !== false) {
                    $horodatages[] = $t;
                }
            }
        }

        return $horodatages ? max($horodatages) : null;
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
