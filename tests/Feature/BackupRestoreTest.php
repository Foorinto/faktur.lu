<?php

namespace Tests\Feature;

use App\Services\BackupService;
use Tests\TestCase;

/**
 * Restauration d'une sauvegarde, cycle complet.
 *
 * L'article 32 du RGPD demande de tester régulièrement l'efficacité des
 * mesures de sécurité. Une sauvegarde jamais restaurée n'est pas une mesure,
 * c'est une hypothèse : rien ne garantit que le fichier produit chaque nuit
 * puisse un jour redevenir une base de données.
 *
 * Le cycle est rejoué ici en entier — dump, compression, chiffrement,
 * déchiffrement, restauration — sur une base jetable, et le contenu est
 * comparé de bout en bout. « Tous les six mois » devient « à chaque
 * modification du code ».
 */
class BackupRestoreTest extends TestCase
{
    private const KEY = 'yT8kQ2mZ7xR4nB9vC1sD6fG3hJ5lP0wA';

    private string $baseJetable;

    /** @var list<string> */
    private array $aNettoyer = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['sqlite3', 'gzip', 'gunzip'] as $binaire) {
            if (! $this->binairePresent($binaire)) {
                $this->markTestSkipped("Le binaire {$binaire} est absent de cet environnement.");
            }
        }

        // Une base à part, jamais celle des tests : la restauration écrase sa
        // cible, et viser la base courante reviendrait à la détruire.
        $this->baseJetable = tempnam(sys_get_temp_dir(), 'restore_cible_').'.sqlite';
        touch($this->baseJetable);
        $this->aNettoyer[] = $this->baseJetable;

        config([
            'backup.encryption_key' => self::KEY,
            'backup.database_connection' => 'sqlite',
            'database.connections.sqlite.database' => $this->baseJetable,
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->aNettoyer as $fichier) {
            @unlink($fichier);
            @unlink($fichier.'.enc');
            @unlink($fichier.'.pre-restore');
        }

        parent::tearDown();
    }

    private function binairePresent(string $binaire): bool
    {
        exec('command -v '.escapeshellarg($binaire), $sortie, $code);

        return $code === 0;
    }

    private function invoke(string $methode, mixed ...$arguments): mixed
    {
        $reflection = new \ReflectionMethod(BackupService::class, $methode);
        $reflection->setAccessible(true);

        return $reflection->invoke(app(BackupService::class), ...$arguments);
    }

    private function sql(string $requete): string
    {
        exec(
            sprintf('sqlite3 %s %s', escapeshellarg($this->baseJetable), escapeshellarg($requete)),
            $sortie
        );

        return implode("\n", $sortie);
    }

    public function test_a_backup_can_become_a_database_again(): void
    {
        $this->sql('CREATE TABLE factures (id INTEGER PRIMARY KEY, numero TEXT, montant REAL);');
        $this->sql("INSERT INTO factures VALUES (1, 'FA-2026-0001', 1234.56);");
        $this->sql("INSERT INTO factures VALUES (2, 'FA-2026-0002', 890.10);");

        // 1. Sauvegarde : dump puis compression.
        $archive = tempnam(sys_get_temp_dir(), 'restore_dump_').'.sql.gz';
        $this->aNettoyer[] = $archive;
        $this->invoke('dumpDatabase', $archive);

        $this->assertFileExists($archive);
        $this->assertGreaterThan(0, filesize($archive), 'Le dump ne doit pas être vide.');

        // 2. Chiffrement, tel qu'il a lieu avant tout départ du serveur.
        $chiffre = $this->invoke('encrypt', $archive);
        $this->aNettoyer[] = $chiffre;

        $this->assertStringNotContainsString(
            'FA-2026-0001',
            (string) file_get_contents($chiffre),
            'Une sauvegarde chiffrée ne doit rien laisser lire de son contenu.'
        );

        // 3. Le sinistre : la base disparaît.
        unlink($this->baseJetable);
        $this->assertFileDoesNotExist($this->baseJetable);

        // 4. Déchiffrement puis restauration.
        $dechiffre = $this->invoke('decrypt', $chiffre);
        $this->aNettoyer[] = $dechiffre;
        $this->invoke('restoreDatabase', $dechiffre);

        // 5. Les données doivent être là, à l'identique.
        $this->assertSame('2', $this->sql('SELECT COUNT(*) FROM factures;'));
        $this->assertSame('FA-2026-0001|1234.56', $this->sql('SELECT numero, montant FROM factures WHERE id = 1;'));
        $this->assertSame('FA-2026-0002|890.1', $this->sql('SELECT numero, montant FROM factures WHERE id = 2;'));
    }

    /**
     * Restaurer par-dessus une base vivante ne doit pas la laisser dans un état
     * intermédiaire : c'est le geste qu'on fait sous pression, un soir
     * d'incident.
     */
    public function test_restoring_over_a_live_database_replaces_it_wholesale(): void
    {
        $this->sql('CREATE TABLE factures (id INTEGER PRIMARY KEY, numero TEXT);');
        $this->sql("INSERT INTO factures VALUES (1, 'FA-2026-0001');");

        $archive = tempnam(sys_get_temp_dir(), 'restore_dump_').'.sql.gz';
        $this->aNettoyer[] = $archive;
        $this->invoke('dumpDatabase', $archive);

        // La base continue de vivre après la sauvegarde.
        $this->sql("INSERT INTO factures VALUES (2, 'FA-2026-0002');");
        $this->assertSame('2', $this->sql('SELECT COUNT(*) FROM factures;'));

        $this->invoke('restoreDatabase', $archive);

        $this->assertSame(
            '1',
            $this->sql('SELECT COUNT(*) FROM factures;'),
            'La restauration doit rendre la base telle qu\'elle était, sans reliquat postérieur.'
        );
    }
}
