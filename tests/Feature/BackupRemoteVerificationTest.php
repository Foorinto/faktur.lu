<?php

namespace Tests\Feature;

use App\Services\BackupService;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * Vérification de l'arrivée d'une sauvegarde sur le dépôt distant.
 *
 * Le contrôle relisait le distant dans la seconde suivant l'envoi. pCloud
 * n'indexe pas instantanément ce qu'il vient de recevoir : il répondait une
 * liste où le fichier ne figurait pas encore, et le journal accusait une panne
 * alors que la sauvegarde était bien arrivée.
 *
 * Une alerte qui se trompe finit par ne plus être lue, et couvre celle qui
 * compte : c'est ce que ces deux cas verrouillent.
 */
class BackupRemoteVerificationTest extends TestCase
{
    private function remoteHasFile(array $delais = [0, 0]): bool
    {
        $service = app(BackupService::class);

        $methode = new \ReflectionMethod($service, 'remoteHasFile');
        $methode->setAccessible(true);

        return $methode->invoke($service, 'pcloud:/Backups/faktur-lu', 'backup_test.sql.gz.enc', $delais);
    }

    public function test_a_listing_that_lags_behind_is_not_a_failure(): void
    {
        // Première relecture : le distant ne voit pas encore le fichier.
        // Seconde : il y est.
        Process::fake([
            '*' => Process::sequence()
                ->push(Process::result(output: ''))
                ->push(Process::result(output: "backup_test.sql.gz.enc\n")),
        ]);

        $this->assertTrue(
            $this->remoteHasFile(),
            'Un dépôt lent à indexer ne doit pas passer pour une sauvegarde manquante.'
        );
    }

    public function test_a_file_that_never_appears_is_still_a_failure(): void
    {
        Process::fake(['*' => Process::result(output: '')]);

        $this->assertFalse(
            $this->remoteHasFile(),
            'Après toutes les relectures, un fichier absent doit rester une panne.'
        );
    }

    public function test_a_file_present_immediately_costs_no_extra_read(): void
    {
        Process::fake(['*' => Process::result(output: "backup_test.sql.gz.enc\n")]);

        $this->assertTrue($this->remoteHasFile());

        Process::assertRanTimes(fn ($process) => str_contains($process->command, 'lsf'), 1);
    }
}
