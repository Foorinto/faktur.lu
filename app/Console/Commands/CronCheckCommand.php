<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

/**
 * Le cron tourne-t-il, et depuis quand ?
 *
 * Né de la panne du 30 juillet : pendant quatre jours, la ligne crontab était
 * correcte, le planificateur fonctionnel, et pourtant aucune sauvegarde n'a été
 * produite. Rien ne permettait de le voir sans aller fouiller pCloud à la main.
 *
 * Le battement lu ici est écrit par la commande `date` du shell, AVANT que PHP
 * ne démarre. C'est ce qui permet de distinguer deux pannes qui se ressemblent
 * de l'extérieur : un cron qui ne se déclenche pas (battement figé) et un cron
 * qui se déclenche mais dont PHP échoue (battement frais, erreurs en journal).
 */
class CronCheckCommand extends Command
{
    protected $signature = 'cron:check {--tasks : Affiche aussi la liste des tâches planifiées}';

    protected $description = 'Vérifie que le cron déclenche bien le planificateur, et signale les erreurs récentes';

    /** Tolérance avant de considérer le battement comme perdu. */
    private const STALE_AFTER_SECONDS = 180;

    public function handle(): int
    {
        $problems = [];

        $heartbeat = storage_path('logs/cron-last-run.txt');
        $errorLog = storage_path('logs/cron-errors.log');

        $this->line('');
        $this->info('── Battement du cron ──');

        // Vérifier la CRONTAB avant le fichier. Le battement peut avoir été
        // écrit à la main — la commande de diagnostic « date > … » produit un
        // fichier rigoureusement identique à celui du cron. Sans ce contrôle,
        // un test manuel fait passer un cron mort pour un cron vivant : c'est
        // arrivé le 2026-08-03, et cette commande a répondu que tout allait bien
        // alors que la crontab n'avait pas été modifiée.
        $declared = $this->heartbeatIsInCrontab();

        if ($declared === false) {
            $this->line('  Ligne crontab         : elle n\'écrit PAS de battement');
            $problems[] = 'La crontab ne contient pas l\'écriture du battement '
                .'(« date > storage/logs/cron-last-run.txt »). Toute fraîcheur du fichier '
                .'ci-dessous provient donc d\'une exécution manuelle, et ne prouve rien '
                .'sur le cron.';
        } elseif ($declared === true) {
            $this->line('  Ligne crontab         : elle écrit bien le battement');
        }

        if (! file_exists($heartbeat)) {
            $this->line('  Fichier de battement  : ABSENT');
            $this->line('  → '.$heartbeat);
            $problems[] = 'La ligne de battement n\'est pas installée dans la crontab. '
                .'Sans elle, impossible de distinguer un cron à l\'arrêt d\'un cron qui échoue.';
        } else {
            $age = time() - filemtime($heartbeat);

            $seconds = (int) date('s', filemtime($heartbeat));
            $suspect = $declared !== true && $seconds > 10;

            $this->line('  Dernier passage       : '.date('Y-m-d H:i:s', filemtime($heartbeat)));
            $this->line('  Il y a                : '.$this->humanize($age));

            // Un cron démarre à la seconde 00 (quelques secondes de gigue au
            // plus). Un horodatage en milieu de minute trahit une écriture
            // manuelle — second garde-fou, indépendant de la lecture de crontab
            // qui n'est pas toujours possible.
            if ($suspect) {
                $this->line('  ⚠ Horodatage à la seconde '.$seconds.' : un cron écrit à la seconde 00.');
                $problems[] = 'Le battement porte un horodatage incompatible avec un déclenchement '
                    .'par cron : il a très probablement été écrit à la main.';
            }

            if ($age > self::STALE_AFTER_SECONDS) {
                $problems[] = 'Le cron ne s\'est pas déclenché depuis '.$this->humanize($age)
                    .' alors qu\'il devrait passer chaque minute : la tâche planifiée est arrêtée '
                    .'côté hébergeur, ou la ligne crontab a été modifiée.';
            } elseif (! $suspect && $declared !== false) {
                $this->line('  État                  : le cron passe bien');
            }
        }

        // --- Erreurs remontées par le cron ---
        $this->line('');
        $this->info('── Erreurs du planificateur ──');

        if (! file_exists($errorLog)) {
            $this->line('  cron-errors.log       : absent');
            $this->line('  (normal si la crontab redirige encore stderr vers /dev/null)');
        } elseif (filesize($errorLog) === 0) {
            $this->line('  cron-errors.log       : vide — aucune erreur remontée');
        } else {
            $lines = array_filter(explode("\n", trim((string) file_get_contents($errorLog))));
            $this->line('  cron-errors.log       : '.count($lines).' ligne(s), '
                .'dernière modification '.date('Y-m-d H:i', filemtime($errorLog)));

            foreach (array_slice($lines, -5) as $line) {
                $this->line('    '.mb_substr(trim($line), 0, 160));
            }

            $problems[] = 'Le planificateur écrit des erreurs dans storage/logs/cron-errors.log '
                .'(voir les dernières lignes ci-dessus).';
        }

        if ($this->option('tasks')) {
            $this->line('');
            $this->info('── Tâches planifiées ──');
            Artisan::call('schedule:list');
            $this->line(Artisan::output());
        }

        // --- Verdict ---
        $this->line('');

        if (empty($problems)) {
            $this->info('✓ Le cron déclenche le planificateur et ne remonte aucune erreur.');
            $this->line('  Contrôle complémentaire : « php artisan backup:check » pour la sauvegarde.');

            return self::SUCCESS;
        }

        $this->error('Problèmes détectés :');
        foreach ($problems as $i => $problem) {
            $this->line('  '.($i + 1).'. '.$problem);
        }

        return self::FAILURE;
    }

    /**
     * La crontab contient-elle l'écriture du battement ?
     *
     * Renvoie null quand la crontab est illisible depuis PHP — on ne peut alors
     * ni confirmer ni infirmer, et seul le contrôle sur l'horodatage s'applique.
     */
    private function heartbeatIsInCrontab(): ?bool
    {
        $crontab = Process::timeout(30)->run('crontab -l');

        if (! $crontab->successful()) {
            $this->line('  Ligne crontab         : illisible depuis PHP');

            return null;
        }

        foreach (explode("\n", $crontab->output()) as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            if (str_contains($line, 'cron-last-run.txt')) {
                return true;
            }
        }

        return false;
    }

    private function humanize(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} s";
        }

        if ($seconds < 3600) {
            return intdiv($seconds, 60).' min';
        }

        if ($seconds < 86400) {
            return intdiv($seconds, 3600).' h';
        }

        return intdiv($seconds, 86400).' jour(s)';
    }
}
