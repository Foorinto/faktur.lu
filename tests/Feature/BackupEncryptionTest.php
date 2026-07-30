<?php

namespace Tests\Feature;

use App\Services\BackupService;
use Tests\TestCase;

/**
 * Chiffrement des sauvegardes.
 *
 * Une sauvegarde qu'on ne sait pas déchiffrer ne vaut rien : l'aller-retour est
 * la seule vérification qui compte. La clé transite par l'environnement et non
 * par la ligne de commande, la liste des processus étant lisible par d'autres
 * comptes sur un hébergement mutualisé.
 */
class BackupEncryptionTest extends TestCase
{
    private const KEY = 'yT8kQ2mZ7xR4nB9vC1sD6fG3hJ5lP0wA';

    private function invoke(BackupService $service, string $method, string $path): string
    {
        $reflection = new \ReflectionMethod($service, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($service, $path);
    }

    public function test_an_encrypted_backup_can_be_decrypted_back_to_the_original(): void
    {
        config(['backup.encryption_key' => self::KEY]);

        $original = tempnam(sys_get_temp_dir(), 'backup_test_');
        $content = "-- dump SQL de test\nINSERT INTO invoices VALUES (1);\n";
        file_put_contents($original, $content);

        $service = app(BackupService::class);

        $encrypted = $this->invoke($service, 'encrypt', $original);

        // Le fichier chiffré ne doit pas laisser lire son contenu.
        $this->assertFileExists($encrypted);
        $this->assertStringNotContainsString('INSERT INTO', file_get_contents($encrypted));

        // Et il doit redonner exactement l'original.
        $decrypted = $this->invoke($service, 'decrypt', $encrypted);

        $this->assertSame($content, file_get_contents($decrypted));

        @unlink($original);
        @unlink($encrypted);
        @unlink($decrypted);
    }

    public function test_a_wrong_key_cannot_decrypt_the_backup(): void
    {
        config(['backup.encryption_key' => self::KEY]);

        $original = tempnam(sys_get_temp_dir(), 'backup_test_');
        file_put_contents($original, 'contenu confidentiel');

        $service = app(BackupService::class);
        $encrypted = $this->invoke($service, 'encrypt', $original);

        // Une clé perdue signifie une sauvegarde définitivement illisible :
        // ce test le rend explicite.
        config(['backup.encryption_key' => 'une-tout-autre-cle-que-la-bonne']);

        $this->expectException(\RuntimeException::class);

        try {
            $this->invoke(app(BackupService::class), 'decrypt', $encrypted);
        } finally {
            @unlink($original);
            @unlink($encrypted);
        }
    }
}
