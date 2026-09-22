<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BusinessSettings;
use App\Models\User;
use App\Notifications\SecurityAlertNotification;
use App\Security\SecurityAlerter;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Alertes de sécurité et gel du compte (FEAT-122, étape 2 du plan).
 *
 * Ce qui est protégé ici : chaque geste sensible prévient le titulaire, à
 * l'adresse du compte et à l'ancienne adresse quand elle vient de changer ;
 * le lien « ce n'était pas moi » gèle le compte sans autre preuve que la
 * signature ; un compte gelé ne se connecte plus, et seule la réinitialisation
 * du mot de passe le rouvre.
 */
class SecurityAlertTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlansSeeder::class);
        Notification::fake();

        $this->user = User::factory()->create([
            'email' => 'titulaire@example.lu',
            'password' => 'password',
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
        $this->actingAs($this->user);
    }

    // --- Outils ----------------------------------------------------------------

    private function reglages(array $surcharge = []): array
    {
        return array_merge([
            'company_name' => 'Ma société',
            'legal_name' => 'Ma société SARL',
            'address' => '1 rue du Test',
            'postal_code' => 'L-1234',
            'city' => 'Luxembourg',
            'country_code' => 'LU',
            'matricule' => '12345678901',
            'iban' => 'LU120010001234567891',
            'bic' => 'BGLLLULL',
            'vat_regime' => 'franchise',
            'email' => 'contact@example.lu',
            'current_password' => 'password',
        ], $surcharge);
    }

    private function changerLIban(): void
    {
        BusinessSettings::factory()->create(['user_id' => $this->user->id, 'iban' => 'LU280019400644750000']);

        $this->put(route('settings.business.update'), $this->reglages())->assertSessionHasNoErrors();
    }

    private function alerteEnvoyeeA(string $adresse, string $event): void
    {
        Notification::assertSentOnDemand(
            SecurityAlertNotification::class,
            fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === $adresse && $n->event === $event
        );
    }

    private function lienDeGel(string $event = 'iban_changed'): string
    {
        return URL::temporarySignedRoute('security.not-me', now()->addDays(7), [
            'user' => $this->user->id,
            'event' => $event,
        ]);
    }

    // --- Qui est prévenu -------------------------------------------------------

    public function test_changer_l_iban_previent_le_titulaire(): void
    {
        $this->changerLIban();

        $this->alerteEnvoyeeA('titulaire@example.lu', 'iban_changed');
    }

    public function test_l_alerte_masque_les_iban(): void
    {
        // Le mail traverse des boîtes qu'on ne contrôle pas : assez pour
        // reconnaître son compte, pas assez pour le recopier.
        $this->changerLIban();

        Notification::assertSentOnDemand(
            SecurityAlertNotification::class,
            fn ($n) => $n->details['iban_from'] === 'LU28 **** **** **** 0000'
                && $n->details['iban_to'] === 'LU12 **** **** **** 7891'
        );
    }

    public function test_changer_l_iban_ne_previent_pas_sans_changement(): void
    {
        BusinessSettings::factory()->create(['user_id' => $this->user->id, 'iban' => 'LU120010001234567891']);

        $this->put(route('settings.business.update'), $this->reglages(['company_name' => 'Autre nom']))
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_changer_l_email_previent_l_ancienne_et_la_nouvelle_adresse(): void
    {
        $this->patch(route('profile.update'), [
            'name' => $this->user->name,
            'email' => 'nouvelle@example.lu',
            'current_password' => 'password',
        ])->assertSessionHasNoErrors();

        $this->alerteEnvoyeeA('titulaire@example.lu', 'email_changed');
        $this->alerteEnvoyeeA('nouvelle@example.lu', 'email_changed');

        $this->assertSame('titulaire@example.lu', $this->user->fresh()->previous_email);
        $this->assertNotNull($this->user->fresh()->email_changed_at);
    }

    public function test_l_ancienne_adresse_reste_prevenue_trente_jours(): void
    {
        // Le scénario redouté : l'attaquant change l'adresse, puis l'IBAN.
        // L'ancienne boîte est le seul canal qui reste au vrai titulaire.
        $this->user->forceFill([
            'previous_email' => 'ancienne@example.lu',
            'email_changed_at' => now()->subDays(10),
        ])->save();

        $this->changerLIban();

        $this->alerteEnvoyeeA('titulaire@example.lu', 'iban_changed');
        $this->alerteEnvoyeeA('ancienne@example.lu', 'iban_changed');
    }

    public function test_l_ancienne_adresse_n_est_plus_prevenue_apres_trente_jours(): void
    {
        $this->user->forceFill([
            'previous_email' => 'ancienne@example.lu',
            'email_changed_at' => now()->subDays(45),
        ])->save();

        $this->changerLIban();

        // Une seule alerte partie : celle de l'adresse du compte. L'ancienne
        // n'est plus dans le cercle.
        $this->alerteEnvoyeeA('titulaire@example.lu', 'iban_changed');
        Notification::assertSentOnDemandTimes(SecurityAlertNotification::class, 1);
    }

    public function test_remplacer_le_qr_de_paiement_previent(): void
    {
        Storage::fake('public');
        BusinessSettings::factory()->create(['user_id' => $this->user->id]);

        $this->post(route('settings.business.payment-qrcode.upload'), [
            'payment_qrcode' => UploadedFile::fake()->image('qr.png', 200, 200),
            'current_password' => 'password',
        ])->assertSessionHasNoErrors();

        $this->alerteEnvoyeeA('titulaire@example.lu', 'payment_qrcode_changed');
    }

    public function test_changer_le_mot_de_passe_previent(): void
    {
        $this->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'Un-nouveau-mot-de-passe-long-42',
            'password_confirmation' => 'Un-nouveau-mot-de-passe-long-42',
        ])->assertSessionHasNoErrors();

        $this->alerteEnvoyeeA('titulaire@example.lu', 'password_changed');
    }

    public function test_desactiver_la_2fa_previent_et_se_journalise(): void
    {
        $this->post('/user/confirm-password', ['password' => 'password']);
        $this->post('/user/two-factor-authentication');
        $code = app(Google2FA::class)->getCurrentOtp(decrypt($this->user->fresh()->two_factor_secret));
        $this->post('/user/confirmed-two-factor-authentication', ['code' => $code]);

        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ACTION_2FA_ENABLED, 'user_id' => $this->user->id]);

        $this->delete('/user/two-factor-authentication');

        $this->alerteEnvoyeeA('titulaire@example.lu', 'two_factor_disabled');
        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ACTION_2FA_DISABLED, 'user_id' => $this->user->id]);
    }

    public function test_une_panne_du_serveur_de_mail_ne_bloque_pas_le_geste(): void
    {
        // Le geste est déjà enregistré quand l'alerte part : une panne d'envoi
        // se journalise, elle ne transforme pas un changement réussi en page
        // d'erreur.
        // Le faux poseur de notifications du setUp répond à tout : on remet le
        // vrai, et on lui donne un transport qui n'existe pas.
        Notification::swap(new ChannelManager(app()));
        config(['mail.default' => 'transport-inexistant']);

        BusinessSettings::factory()->create(['user_id' => $this->user->id, 'iban' => 'LU280019400644750000']);

        $this->put(route('settings.business.update'), $this->reglages())->assertSessionHasNoErrors();

        $this->assertSame('LU120010001234567891', BusinessSettings::first()->iban);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'security.alert_sent',
            'user_id' => $this->user->id,
            'status' => AuditLog::STATUS_FAILED,
        ]);
    }

    public function test_l_alerte_est_journalisee(): void
    {
        $this->changerLIban();

        $this->assertDatabaseHas('audit_logs', ['action' => 'security.alert_sent', 'user_id' => $this->user->id]);
    }

    // --- Le lien « ce n'était pas moi » ---------------------------------------

    public function test_le_lien_gele_le_compte_et_ferme_les_sessions(): void
    {
        // Les tests tournent avec des sessions en mémoire ; la production les
        // garde en base, et c'est là que le gel doit les effacer.
        config(['session.driver' => 'database']);

        // Une session ouverte ailleurs, celle de l'attaquant par exemple.
        DB::table('sessions')->insert([
            'id' => 'session-de-l-attaquant',
            'user_id' => $this->user->id,
            'ip_address' => '203.0.113.7',
            'user_agent' => 'test',
            'payload' => base64_encode(serialize([])),
            'last_activity' => time(),
        ]);
        $ancienJeton = $this->user->remember_token;

        Auth::logout();
        $this->get($this->lienDeGel())->assertOk()->assertInertia(fn ($page) => $page
            ->component('Auth/AccountLocked')
            ->where('event', 'iban_changed')
            ->where('alreadyLocked', false)
        );

        $this->assertNotNull($this->user->fresh()->security_locked_at);
        $this->assertDatabaseMissing('sessions', ['id' => 'session-de-l-attaquant']);
        $this->assertNotSame($ancienJeton, $this->user->fresh()->remember_token);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.locked_by_owner', 'user_id' => $this->user->id]);
    }

    public function test_un_lien_altere_est_refuse(): void
    {
        Auth::logout();

        $this->get($this->lienDeGel().'x')->assertForbidden();
        $this->assertNull($this->user->fresh()->security_locked_at);
    }

    public function test_un_lien_expire_est_refuse(): void
    {
        $lien = $this->lienDeGel();
        Auth::logout();

        $this->travel(8)->days();

        $this->get($lien)->assertForbidden();
        $this->assertNull($this->user->fresh()->security_locked_at);
    }

    public function test_rejouer_le_lien_ne_change_rien(): void
    {
        Auth::logout();
        $this->get($this->lienDeGel());
        $premier = $this->user->fresh()->security_locked_at;

        $this->travel(1)->hour();
        $this->get($this->lienDeGel())->assertOk()->assertInertia(fn ($page) => $page->where('alreadyLocked', true));

        $this->assertEquals($premier, $this->user->fresh()->security_locked_at);
    }

    // --- Un compte gelé --------------------------------------------------------

    public function test_un_compte_gele_ne_se_connecte_plus(): void
    {
        $this->user->forceFill(['security_locked_at' => now()])->save();
        Auth::logout();

        $this->post(route('login'), ['email' => 'titulaire@example.lu', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_le_gel_tient_aussi_avec_une_adresse_en_majuscules(): void
    {
        // Le gel doit chercher l'utilisateur exactement comme la connexion :
        // sur une base sensible à la casse, une normalisation d'un seul côté
        // laisserait passer qui tape son adresse comme elle est enregistrée.
        $this->user->forceFill(['email' => 'Titulaire@Example.lu', 'security_locked_at' => now()])->save();
        Auth::logout();

        $this->post(route('login'), ['email' => 'Titulaire@Example.lu', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_la_reinitialisation_du_mot_de_passe_leve_le_gel(): void
    {
        $this->user->forceFill(['security_locked_at' => now()])->save();
        Auth::logout();

        $token = Password::createToken($this->user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => 'titulaire@example.lu',
            'password' => 'Un-nouveau-mot-de-passe-long-42',
            'password_confirmation' => 'Un-nouveau-mot-de-passe-long-42',
        ])->assertSessionHasNoErrors();

        $this->assertNull($this->user->fresh()->security_locked_at);

        $this->post(route('login'), ['email' => 'titulaire@example.lu', 'password' => 'Un-nouveau-mot-de-passe-long-42'])
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_le_masquage_d_iban_garde_le_debut_et_la_fin(): void
    {
        $this->assertSame('LU28 **** **** **** 0000', SecurityAlerter::maskIban('lu28 0019 4006 4475 0000'));
        $this->assertSame('', SecurityAlerter::maskIban(null));
    }
}
