<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

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

        if (! file_exists($heartbeat)) {
            $this->line('  Fichier de battement  : ABSENT');
            $this->line('  → '.$heartbeat);
            $problems[] = 'La ligne de battement n\'est pas installée dans la crontab. '
                .'Sans elle, impossible de distinguer un cron à l\'arrêt d\'un cron qui échoue.';
        } else {
            $age = time() - filemtime($heartbeat);

            $this->line('  Dernier passage       : '.date('Y-m-d H:i:s', filemtime($heartbeat)));
            $this->line('  Il y a                : '.$this->humanize($age));

            if ($age > self::STALE_AFTER_SECONDS) {
                $problems[] = 'Le cron ne s\'est pas déclenché depuis '.$this->humanize($age)
                    .' alors qu\'il devrait passer chaque minute : la tâche planifiée est arrêtée '
                    .'côté hébergeur, ou la ligne crontab a été modifiée.';
            } else {
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
