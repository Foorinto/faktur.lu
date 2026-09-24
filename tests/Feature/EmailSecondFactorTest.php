<?php

namespace Tests\Feature;

use App\Auth\EmailOtp;
use App\Auth\TrustedDevices;
use App\Models\AuditLog;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Notifications\EmailOtpNotification;
use App\Notifications\SecurityAlertNotification;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Le second facteur par e-mail (FEAT-124) : après le mot de passe, un code à
 * six chiffres envoyé à l'adresse du compte. Ce qui est protégé ici : le mot
 * de passe seul ne suffit plus, le code ne sert qu'une fois et expire, un
 * appareil mémorisé évite le défi trente jours mais pas pour un autre compte,
 * la réauthentification à l'acte exige le même code, et un compte exposé ne
 * revient jamais à « aucun second facteur ».
 */
class EmailSecondFactorTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Fakt#2026!Secur';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlansSeeder::class);
        Notification::fake();
    }

    // --- Outils ----------------------------------------------------------------

    private function utilisateur(array $attributs = []): User
    {
        return User::factory()->create(array_merge([
            'password' => Hash::make(self::PASSWORD),
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ], $attributs));
    }

    private function avecCodeEmail(array $attributs = []): User
    {
        return $this->utilisateur(array_merge(['email_otp_enabled_at' => now()], $attributs));
    }

    private function avecApplication(User $user): string
    {
        $secret = app(Google2FA::class)->generateSecretKey();
        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-de-secours-1'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $secret;
    }

    /** IBAN enregistré et une facture finalisée : le compte est exposé. */
    private function exposer(User $user): void
    {
        BusinessSettings::factory()->create(['user_id' => $user->id, 'iban' => 'LU280019400644750000']);
        Invoice::factory()->finalized()->create([
            'user_id' => $user->id,
            'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
        ]);
    }

    private function connexion(User $user): TestResponse
    {
        return $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD]);
    }

    /** Le dernier code parti par e-mail pour ce compte. */
    private function codeEnvoye(User $user): string
    {
        $code = null;
        Notification::assertSentTo($user, EmailOtpNotification::class, function (EmailOtpNotification $n) use (&$code) {
            $code = $n->code;

            return true;
        });
        $this->assertNotNull($code);

        return $code;
    }

    private function appareilMemorise(User $user): void
    {
        TrustedDevice::create([
            'user_id' => $user->id,
            'token' => hash('sha256', 'jeton'),
            'expires_at' => now()->addDays(30),
        ]);
    }

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
            'iban' => 'LU280019400644750000',
            'bic' => 'BGLLLULL',
            'vat_regime' => 'franchise',
            'exercise_form' => 'liberal',
            'no_establishment_authorization' => true,
            'email' => 'contact@example.lu',
        ], $surcharge);
    }

    // --- La connexion ----------------------------------------------------------

    public function test_le_mot_de_passe_seul_ne_connecte_pas_et_un_code_part_par_email(): void
    {
        $user = $this->avecCodeEmail();

        $this->connexion($user)->assertRedirect(route('two-factor.email'));

        $this->assertGuest();
        Notification::assertSentTo($user, EmailOtpNotification::class);
        $this->get(route('two-factor.email'))->assertOk();
    }

    public function test_sans_defi_en_cours_la_page_renvoie_a_la_connexion(): void
    {
        $this->get(route('two-factor.email'))->assertRedirect(route('login'));
        $this->post(route('two-factor.email.store'), ['code' => '123456'])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_le_bon_code_connecte_et_ne_sert_qu_une_fois(): void
    {
        $user = $this->avecCodeEmail();
        $this->connexion($user);
        $code = $this->codeEnvoye($user);

        // « 123 456 », tel qu'on le recopie depuis le mail.
        $this->post(route('two-factor.email.store'), ['code' => substr($code, 0, 3).' '.substr($code, 3)])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->email_otp_code);
        $this->assertFalse(app(EmailOtp::class)->verify($user->fresh(), $code));
    }

    public function test_un_mauvais_code_ne_connecte_pas_et_cinq_erreurs_tuent_le_code(): void
    {
        $user = $this->avecCodeEmail();
        $this->connexion($user);
        $code = $this->codeEnvoye($user);
        $faux = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < EmailOtp::MAX_ATTEMPTS; $i++) {
            $this->post(route('two-factor.email.store'), ['code' => $faux])->assertSessionHasErrors('code');
        }

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => AuditLog::ACTION_EMAIL_OTP_FAILED]);

        // Même le bon code ne passe plus : il faut en redemander un.
        $this->post(route('two-factor.email.store'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_un_code_expire_est_refuse(): void
    {
        $user = $this->avecCodeEmail();
        $this->connexion($user);
        $code = $this->codeEnvoye($user);

        $this->travel(EmailOtp::VALIDITY_MINUTES + 1)->minutes();

        $this->post(route('two-factor.email.store'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_renvoyer_le_code_attend_trente_secondes(): void
    {
        $user = $this->avecCodeEmail();
        $this->connexion($user);

        $this->post(route('two-factor.email.resend'))->assertSessionHasErrors('code');
        Notification::assertSentToTimes($user, EmailOtpNotification::class, 1);

        $this->travel(EmailOtp::RESEND_SECONDS + 1)->seconds();

        $this->post(route('two-factor.email.resend'))->assertSessionHasNoErrors()->assertSessionHas('status', 'resent');
        Notification::assertSentToTimes($user, EmailOtpNotification::class, 2);
    }

    public function test_un_compte_gele_ne_passe_pas_le_defi(): void
    {
        $user = $this->avecCodeEmail();
        $this->connexion($user);
        $code = $this->codeEnvoye($user);
        $user->forceFill(['security_locked_at' => now()])->save();

        $this->post(route('two-factor.email.store'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_l_application_prend_le_dessus_sur_le_code_par_email(): void
    {
        $user = $this->avecCodeEmail();
        $this->avecApplication($user);

        $this->connexion($user)->assertRedirect(route('two-factor.login'));

        Notification::assertNothingSent();
        $this->assertSame('app', $user->fresh()->secondFactor());
    }

    // --- Les appareils mémorisés -------------------------------------------------

    public function test_un_appareil_memorise_evite_le_defi_pendant_trente_jours(): void
    {
        $user = $this->avecCodeEmail();
        $this->connexion($user);
        $code = $this->codeEnvoye($user);

        $reponse = $this->post(route('two-factor.email.store'), ['code' => $code, 'remember_device' => '1']);
        $this->assertAuthenticatedAs($user);
        $cookie = $reponse->getCookie(TrustedDevices::COOKIE);
        $this->assertNotNull($cookie);
        $this->assertDatabaseCount('trusted_devices', 1);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => AuditLog::ACTION_TRUSTED_DEVICE_ADDED]);
        // La base ne garde que l'empreinte : le jeton du cookie n'y est pas.
        $this->assertDatabaseMissing('trusted_devices', ['token' => explode('|', $cookie->getValue(), 2)[1]]);

        // Nouvelle session sur le même navigateur : pas de défi, pas de mail.
        $this->post(route('logout'));
        $this->assertGuest();
        $this->withCookie(TrustedDevices::COOKIE, $cookie->getValue());
        $this->connexion($user)->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        Notification::assertSentToTimes($user, EmailOtpNotification::class, 1);

        // Trente jours plus tard : le défi revient.
        $this->post(route('logout'));
        $this->travel(TrustedDevices::DAYS + 1)->days();
        $this->connexion($user)->assertRedirect(route('two-factor.email'));
        $this->assertGuest();
    }

    public function test_le_cookie_d_un_autre_compte_ne_vaut_rien(): void
    {
        $user = $this->avecCodeEmail();
        $autre = $this->avecCodeEmail();
        $this->connexion($autre);
        $cookie = $this->post(route('two-factor.email.store'), ['code' => $this->codeEnvoye($autre), 'remember_device' => '1'])
            ->getCookie(TrustedDevices::COOKIE);
        $this->post(route('logout'));

        $this->withCookie(TrustedDevices::COOKIE, $cookie->getValue());
        $this->connexion($user)->assertRedirect(route('two-factor.email'));
        $this->assertGuest();
    }

    public function test_un_cookie_forge_ne_vaut_rien(): void
    {
        $user = $this->avecCodeEmail();
        $this->appareilMemorise($user);

        $this->withCookie(TrustedDevices::COOKIE, $user->id.'|pas-le-bon-jeton');
        $this->connexion($user)->assertRedirect(route('two-factor.email'));
        $this->assertGuest();
    }

    public function test_le_changement_de_mot_de_passe_oublie_les_appareils_et_exige_le_code(): void
    {
        $user = $this->avecCodeEmail();
        $this->appareilMemorise($user);
        $this->actingAs($user);

        // Sans code par e-mail : refusé.
        $this->put(route('password.update'), [
            'current_password' => self::PASSWORD,
            'password' => 'Nouveau#2026!Secur',
            'password_confirmation' => 'Nouveau#2026!Secur',
        ])->assertSessionHasErrors('two_factor_code');
        $this->assertDatabaseCount('trusted_devices', 1);

        // Le code demandé depuis le formulaire, puis fourni.
        $this->post(route('security.email-code.send'))->assertOk()->assertJsonPath('sent', true);
        $this->put(route('password.update'), [
            'current_password' => self::PASSWORD,
            'two_factor_code' => $this->codeEnvoye($user),
            'password' => 'Nouveau#2026!Secur',
            'password_confirmation' => 'Nouveau#2026!Secur',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('trusted_devices', 0);
    }

    public function test_le_gel_du_compte_oublie_les_appareils(): void
    {
        $user = $this->avecCodeEmail();
        $this->appareilMemorise($user);

        $lien = URL::temporarySignedRoute('security.not-me', now()->addDay(), ['user' => $user->id, 'event' => 'iban_changed']);
        $this->get($lien)->assertOk();

        $this->assertNotNull($user->fresh()->security_locked_at);
        $this->assertDatabaseCount('trusted_devices', 0);
    }

    // --- La réauthentification à l'acte ---------------------------------------------

    public function test_changer_l_iban_exige_le_code_par_email(): void
    {
        $user = $this->avecCodeEmail();
        BusinessSettings::factory()->create(['user_id' => $user->id, 'iban' => 'LU280019400644750000']);
        $this->actingAs($user);

        $this->put(route('settings.business.update'), $this->reglages([
            'iban' => 'LU120010001234567891',
            'current_password' => self::PASSWORD,
        ]))->assertSessionHasErrors('two_factor_code');
        $this->assertSame('LU280019400644750000', BusinessSettings::withoutGlobalScopes()->where('user_id', $user->id)->first()->iban);

        $this->post(route('security.email-code.send'))->assertOk();
        $this->put(route('settings.business.update'), $this->reglages([
            'iban' => 'LU120010001234567891',
            'current_password' => self::PASSWORD,
            'two_factor_code' => $this->codeEnvoye($user),
        ]))->assertSessionHasNoErrors();
        $this->assertSame('LU120010001234567891', BusinessSettings::withoutGlobalScopes()->where('user_id', $user->id)->first()->iban);
    }

    public function test_demander_un_code_est_refuse_a_qui_n_a_pas_le_code_par_email(): void
    {
        $this->actingAs($this->utilisateur())->postJson(route('security.email-code.send'))->assertStatus(422);

        Notification::assertNothingSent();
    }

    public function test_la_confirmation_du_mot_de_passe_exige_aussi_le_code(): void
    {
        $user = $this->avecCodeEmail();
        $this->actingAs($user);

        $this->get(route('password.confirm'))->assertOk()
            ->assertInertia(fn ($page) => $page->where('secondFactor', 'email')->where('requiresTwoFactor', true));

        $this->post('/user/confirm-password', ['password' => self::PASSWORD])->assertSessionHasErrors('two_factor_code');

        $this->post(route('security.email-code.send'))->assertOk();
        $this->post('/user/confirm-password', ['password' => self::PASSWORD, 'two_factor_code' => $this->codeEnvoye($user)])
            ->assertSessionHasNoErrors();
        $this->assertNotNull(session('auth.password_confirmed_at'));
    }

    // --- Le profil -----------------------------------------------------------------

    public function test_activer_le_code_par_email_demande_la_confirmation_du_mot_de_passe(): void
    {
        $user = $this->utilisateur();
        $this->actingAs($user);

        $this->post(route('email-second-factor.enable'))->assertRedirect(route('password.confirm'));
        $this->assertNull($user->fresh()->email_otp_enabled_at);

        session(['auth.password_confirmed_at' => time()]);
        $this->post(route('email-second-factor.enable'))->assertSessionHasNoErrors();

        $this->assertNotNull($user->fresh()->email_otp_enabled_at);
        $this->assertSame('email', $user->fresh()->secondFactor());
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => AuditLog::ACTION_EMAIL_OTP_ENABLED]);
    }

    public function test_un_compte_non_expose_desactive_le_code_par_email_et_recoit_l_alerte(): void
    {
        $user = $this->avecCodeEmail();
        $this->appareilMemorise($user);
        $this->actingAs($user);
        session(['auth.password_confirmed_at' => time()]);

        $this->delete(route('email-second-factor.disable'))->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->email_otp_enabled_at);
        $this->assertDatabaseCount('trusted_devices', 0);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => AuditLog::ACTION_EMAIL_OTP_DISABLED]);
        Notification::assertSentOnDemand(SecurityAlertNotification::class);
    }

    public function test_un_compte_expose_ne_desactive_pas_son_dernier_facteur(): void
    {
        $user = $this->avecCodeEmail();
        $this->exposer($user);
        $this->actingAs($user);
        session(['auth.password_confirmed_at' => time()]);

        $this->delete(route('email-second-factor.disable'))->assertSessionHasErrors('email_otp');
        $this->assertNotNull($user->fresh()->email_otp_enabled_at);
        $this->get(route('profile.edit'))->assertInertia(fn ($page) => $page->where('secondFactorRequired', true));

        // Avec l'application en relais, oui.
        $this->avecApplication($user);
        $this->delete(route('email-second-factor.disable'))->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->email_otp_enabled_at);
    }

    public function test_un_compte_expose_ne_desactive_pas_l_application_sans_le_code_par_email(): void
    {
        $user = $this->utilisateur();
        $this->avecApplication($user);
        $this->exposer($user);
        $this->actingAs($user);
        session(['auth.password_confirmed_at' => time()]);

        $this->deleteJson('/user/two-factor-authentication')->assertStatus(422)->assertJsonValidationErrors('two_factor');
        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);

        $user->forceFill(['email_otp_enabled_at' => now()])->save();
        $this->deleteJson('/user/two-factor-authentication')->assertOk();
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
        $this->assertSame('email', $user->fresh()->secondFactor());
    }

    public function test_un_compte_non_expose_desactive_l_application_comme_avant(): void
    {
        $user = $this->utilisateur();
        $this->avecApplication($user);
        $this->actingAs($user);
        session(['auth.password_confirmed_at' => time()]);

        $this->deleteJson('/user/two-factor-authentication')->assertOk();
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_les_props_partagees_disent_la_methode_sans_livrer_le_code(): void
    {
        $user = $this->avecCodeEmail();
        app(EmailOtp::class)->send($user);

        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn ($page) => $page
            ->where('auth.user.second_factor', 'email')
            ->missing('auth.user.email_otp_code'));
    }
}
