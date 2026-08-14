<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * Le contrôle de sauvegarde ne doit pas confondre un dump et une copie du .env.
 *
 * Depuis que le .env part avec la base, le dépôt contient deux familles de
 * fichiers. Les compter ensemble rouvrait le faux vert qu'on venait de fermer :
 * une copie du .env déposée cette nuit suffisait à satisfaire le contrôle de
 * fraîcheur, alors que plus aucune base ne partait.
 */
class BackupCheckSeparationTest extends TestCase
{
    private string $dossier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dossier = sys_get_temp_dir().'/check_'.bin2hex(random_bytes(4));
        mkdir($this->dossier, 0700, true);

        config([
            'backup.enabled' => true,
            'backup.local.path' => $this->dossier,
            'backup.cloud.enabled' => false,
            'backup.notification_email' => 'alerte@example.test',
        ]);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dossier.'/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dossier);

        parent::tearDown();
    }

    private function fichier(string $nom, int $ageHeures): void
    {
        $chemin = $this->dossier.'/'.$nom;
        file_put_contents($chemin, 'x');
        touch($chemin, time() - $ageHeures * 3600);
    }

    /**
     * Le cas qui compte : les dumps se sont arrêtés il y a quatre jours, les
     * copies du .env continuent. Le contrôle doit voir la panne.
     */
    public function test_a_fresh_env_copy_does_not_hide_a_stale_dump(): void
    {
        Process::fake();

        $this->fichier('backup_2026-08-10_030000.sql.gz.enc', 96);
        $this->fichier('backup_2026-08-14_030000.env.enc', 1);

        $this->artisan('backup:check')
            ->expectsOutputToContain('Dumps                 : 1')
            ->expectsOutputToContain('Copies du .env        : 1')
            ->assertFailed();
    }

    public function test_a_recent_dump_passes_the_freshness_check(): void
    {
        Process::fake();

        $this->fichier('backup_2026-08-14_030000.sql.gz.enc', 2);
        $this->fichier('backup_2026-08-14_030000.env.enc', 2);

        // On n'assertit pas le verdict global : sans crontab réelle, le
        // contrôle signale déjà d'autres manques. Seule compte ici l'absence du
        // reproche de péremption.
        $this->artisan('backup:check')
            ->expectsOutputToContain('Dumps                 : 1')
            ->expectsOutputToContain('Copies du .env        : 1')
            ->doesntExpectOutputToContain('la planification est en panne');
    }

    /**
     * Des copies du .env sans aucun dump ne sont pas une chaîne de sauvegarde :
     * la base, elle, n'est nulle part.
     */
    public function test_env_copies_alone_are_not_a_backup(): void
    {
        Process::fake();

        $this->fichier('backup_2026-08-14_030000.env.enc', 1);

        $this->artisan('backup:check')
            ->expectsOutputToContain('Aucune sauvegarde locale')
            ->assertFailed();
    }
}
