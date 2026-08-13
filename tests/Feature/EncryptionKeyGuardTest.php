<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\User;
use App\Services\EncryptionKeyGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Vigie de la clé d'application.
 *
 * `APP_KEY` déchiffre les IBAN, la configuration de messagerie et les données
 * personnelles des salariés. Le danger n'est pas qu'elle change — c'est que
 * rien ne le signale : les lectures échouent une à une dans des écrans peu
 * visités, pendant que les écritures suivantes se font sous la nouvelle clé.
 * Quelques jours plus tard, deux jeux de données coexistent qu'aucune clé
 * unique ne peut plus lire, et rétablir l'ancienne ne suffit plus.
 */
class EncryptionKeyGuardTest extends TestCase
{
    use RefreshDatabase;

    private function garde(): EncryptionKeyGuard
    {
        return app(EncryptionKeyGuard::class);
    }

    private function uneDonneeChiffree(): void
    {
        $this->actingAs(User::factory()->create());
        BusinessSettings::factory()->create(['iban' => 'LU28 0019 4006 4475 0000']);
    }

    /**
     * Substitue une autre clé, comme le ferait un `.env` modifié.
     *
     * Il ne suffit pas de changer la configuration : le chiffreur est un
     * singleton, déjà construit avec l'ancienne clé dès qu'une valeur a été
     * chiffrée. En production, toucher au `.env` relance le processus PHP et
     * le chiffreur naît avec la nouvelle clé — on reproduit cela en oubliant
     * l'instance.
     */
    private function autreCle(): void
    {
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

        $this->app->forgetInstance('encrypter');
        $this->app->forgetInstance(\Illuminate\Contracts\Encryption\Encrypter::class);
        \Illuminate\Support\Facades\Crypt::clearResolvedInstances();
    }

    public function test_the_first_run_records_the_fingerprint(): void
    {
        $resultat = $this->garde()->verifier();

        $this->assertSame(EncryptionKeyGuard::ETAT_PREMIER_ENREGISTREMENT, $resultat['etat']);
        $this->assertDatabaseCount('encryption_fingerprints', 1);
    }

    public function test_an_unchanged_key_passes_quietly(): void
    {
        $this->uneDonneeChiffree();
        $this->garde()->verifier();

        $resultat = $this->garde()->verifier();

        $this->assertSame(EncryptionKeyGuard::ETAT_CONFORME, $resultat['etat']);
        $this->assertSame(0, $resultat['echecs']);
        $this->assertGreaterThan(0, $resultat['echantillons'], 'Le contrôle doit éprouver une valeur réelle.');
    }

    /**
     * Le cas grave : la clé a changé ET des données deviennent illisibles.
     */
    public function test_unreadable_data_is_reported_as_the_serious_case(): void
    {
        $this->uneDonneeChiffree();
        $this->garde()->verifier();

        $this->autreCle();
        $resultat = $this->garde()->verifier();

        $this->assertSame(EncryptionKeyGuard::ETAT_DONNEES_ILLISIBLES, $resultat['etat']);
        $this->assertGreaterThan(0, $resultat['echecs']);
        $this->assertStringContainsString('NE RIEN ÉCRIRE', $resultat['message']);
    }

    /**
     * Une clé changée sur une base sans donnée chiffrée est bénigne. La
     * confondre avec le cas grave ferait crier l'alerte pour rien — et une
     * alerte qui se trompe finit par ne plus être lue.
     */
    public function test_a_changed_key_without_encrypted_data_is_only_a_warning(): void
    {
        $this->garde()->verifier();

        $this->autreCle();
        $resultat = $this->garde()->verifier();

        $this->assertSame(EncryptionKeyGuard::ETAT_CHANGEE_SANS_DEGAT, $resultat['etat']);
        $this->assertSame(0, $resultat['echecs']);
    }

    public function test_an_accepted_rotation_stops_the_warning(): void
    {
        $this->garde()->verifier();
        $this->autreCle();

        $this->garde()->accepterLaCleCourante();

        $this->assertSame(EncryptionKeyGuard::ETAT_CONFORME, $this->garde()->verifier()['etat']);
    }

    /**
     * La table ne doit aider personne à déchiffrer quoi que ce soit.
     */
    public function test_the_key_itself_is_never_stored(): void
    {
        $this->garde()->verifier();

        $empreinte = DB::table('encryption_fingerprints')->value('fingerprint');
        $cle = (string) config('app.key');

        $this->assertNotSame($cle, $empreinte);
        $this->assertStringNotContainsString(substr($cle, 7, 20), $empreinte);
        $this->assertSame(64, strlen($empreinte), 'Un SHA-256, donc 64 caractères hexadécimaux.');
    }

    public function test_the_command_fails_loudly_so_the_cron_reports_it(): void
    {
        $this->uneDonneeChiffree();
        $this->artisan('encryption:check')->assertSuccessful();

        $this->autreCle();

        $this->artisan('encryption:check')->assertFailed();
    }

    public function test_the_command_can_accept_a_deliberate_rotation(): void
    {
        $this->garde()->verifier();
        $this->autreCle();

        $this->artisan('encryption:check', ['--accepter' => true])->assertSuccessful();
        $this->artisan('encryption:check')->assertSuccessful();
    }
}
