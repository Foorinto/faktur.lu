<?php

namespace Tests\Feature;

use App\Services\BackupService;
use Tests\TestCase;

/**
 * Copie du fichier .env dans la sauvegarde.
 *
 * Le dump ne contient que la base. Les colonnes chiffrées au repos — IBAN de
 * l'entreprise, IBAN des salariés, configuration de messagerie — ne se
 * relisent qu'avec APP_KEY, qui vit dans .env. Sans cette copie, restaurer une
 * base après la perte du serveur les rendait définitivement illisibles : le
 * seul endroit détenant la clé était celui contre la perte duquel on
 * sauvegarde.
 *
 * La contrepartie est immédiate : ce fichier porte TOUS les secrets. D'où la
 * règle que ces tests verrouillent — jamais en clair, à aucune condition.
 */
class BackupEnvFileTest extends TestCase
{
    private const KEY = 'yT8kQ2mZ7xR4nB9vC1sD6fG3hJ5lP0wA';

    /** @var list<string> */
    private array $aNettoyer = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->dossier = sys_get_temp_dir().'/backup_env_'.bin2hex(random_bytes(4));
        mkdir($this->dossier, 0700, true);

        config(['backup.local.path' => $this->dossier]);
    }

    private string $dossier;

    protected function tearDown(): void
    {
        foreach (glob($this->dossier.'/*') ?: [] as $fichier) {
            @unlink($fichier);
        }
        @rmdir($this->dossier);

        parent::tearDown();
    }

    private function sauvegarderEnv(): ?string
    {
        $methode = new \ReflectionMethod(BackupService::class, 'backupEnvFile');
        $methode->setAccessible(true);

        return $methode->invoke(app(BackupService::class), '2026-08-12_030003');
    }

    public function test_the_env_copy_is_encrypted(): void
    {
        config(['backup.encryption_key' => self::KEY]);

        $chemin = $this->sauvegarderEnv();

        $this->assertNotNull($chemin, 'Une copie devait être produite.');
        $this->assertStringEndsWith('.env.enc', $chemin);

        // Le contenu ne doit rien laisser deviner : on cherche un nom de
        // variable que tout .env contient.
        $this->assertStringNotContainsString(
            'APP_KEY',
            (string) file_get_contents($chemin),
            'Le contenu du .env ne doit pas être lisible dans la copie.'
        );
    }

    /**
     * Le cas qui compte. Déposer chez un tiers un fichier portant le mot de
     * passe de la base, les clés Stripe et les identifiants de messagerie
     * serait strictement pire que de ne rien sauvegarder.
     */
    public function test_without_an_encryption_key_nothing_leaves_at_all(): void
    {
        config(['backup.encryption_key' => null]);

        $this->assertNull($this->sauvegarderEnv(), 'Aucune copie ne doit être produite en clair.');

        $this->assertSame(
            [],
            glob($this->dossier.'/*.env') ?: [],
            'Aucun .env en clair ne doit subsister sur le disque.'
        );
    }

    public function test_the_copy_is_readable_again_with_the_key(): void
    {
        config(['backup.encryption_key' => self::KEY]);

        $chiffre = $this->sauvegarderEnv();

        $dechiffrer = new \ReflectionMethod(BackupService::class, 'decrypt');
        $dechiffrer->setAccessible(true);
        $clair = $dechiffrer->invoke(app(BackupService::class), $chiffre);

        $this->assertSame(
            file_get_contents(base_path('.env')),
            file_get_contents($clair),
            'Le déchiffrement doit rendre le fichier à l\'identique.'
        );

        @unlink($clair);
    }

    /**
     * La copie ne doit pas apparaître parmi les sauvegardes proposées à la
     * restauration : ce n'est pas un dump, et la restaurer n'aurait aucun sens.
     */
    public function test_the_copy_never_shows_up_as_a_restorable_backup(): void
    {
        config(['backup.encryption_key' => self::KEY]);

        $this->sauvegarderEnv();

        $noms = array_column(app(BackupService::class)->listLocal(), 'filename');

        $this->assertEmpty(
            array_filter($noms, fn ($n) => str_contains($n, '.env')),
            'listLocal() ne doit énumérer que des dumps restaurables.'
        );
    }
}
