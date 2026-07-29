<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Garde-fou contre un piège récurrent du projet : les tests tournent sur SQLite,
 * la production sur MySQL. Du SQL propre à SQLite passe donc toute la suite au
 * vert et casse en production — c'est exactement ce qui est arrivé au tableau de
 * bord comptable, où `strftime()` (inexistant en MySQL) faisait planter la page
 * de consultation d'un client.
 *
 * Toute expression dépendante du moteur doit passer par App\Helpers\DatabaseHelper.
 */
class NoSqliteOnlySqlTest extends TestCase
{
    /**
     * Fichiers autorisés à mentionner ces fonctions : l'abstraction elle-même,
     * et les rares services qui aiguillent explicitement selon le driver.
     */
    private const ALLOWED = [
        'app/Helpers/DatabaseHelper.php',
        'app/Services/MonitoringService.php',
    ];

    /** Fonctions de date propres à SQLite, absentes de MySQL. */
    private const SQLITE_ONLY = ['strftime(', 'julianday(', "datetime('now'"];

    public function test_no_sqlite_specific_sql_outside_the_database_helper(): void
    {
        $root = dirname(__DIR__, 2);
        $offenders = [];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root.'/app', \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace($root.'/', '', $file->getPathname());

            if (in_array($relative, self::ALLOWED, true)) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            foreach ($contents === false ? [] : explode("\n", $contents) as $number => $line) {
                // Les commentaires peuvent évoquer ces fonctions sans les employer.
                $trimmed = ltrim($line);
                if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*')) {
                    continue;
                }

                foreach (self::SQLITE_ONLY as $needle) {
                    if (str_contains($line, $needle)) {
                        $offenders[] = "{$relative}:".($number + 1)." → {$needle}";
                    }
                }
            }
        }

        $this->assertSame([], $offenders, implode("\n", array_merge(
            ['SQL spécifique à SQLite détecté hors DatabaseHelper.'],
            ['Ces expressions échouent en MySQL, donc en production, sans que les tests le voient.'],
            ['Utilisez App\Helpers\DatabaseHelper (year / month / distinctYear).'],
            $offenders
        )));
    }
}
