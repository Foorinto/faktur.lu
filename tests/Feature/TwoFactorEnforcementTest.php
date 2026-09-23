<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Notifications\TwoFactorNoticeNotification;
use App\Security\TwoFactorPolicy;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * L'obligation d'un second facteur pour les comptes exposés (FEAT-124) : IBAN
 * enregistré et facture émise. Préavis de quatorze jours, rappel deux jours
 * avant, puis le code par e-mail s'active tout seul. Personne n'est enfermé
 * dehors, et la simulation ne touche à rien.
 */
class TwoFactorEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Fakt#2026!Secur';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlansSeeder::class);
        Notification::fake();
        config(['security.enforce_two_factor' => true]);
    }

    private function compte(array $attributs = []): User
    {
        return User::factory()->create(array_merge([
            'password' => Hash::make(self::PASSWORD),
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ], $attributs));
    }

    private function avecIban(User $user): void
    {
        BusinessSettings::factory()->create(['user_id' => $user->id, 'iban' => 'LU280019400644750000']);
    }

    private function avecFacture(User $user): void
    {
        Invoice::factory()->finalized()->create([
            'user_id' => $user->id,
            'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
        ]);
    }

    private function exposer(User $user): void
    {
        $this->avecIban($user);
        $this->avecFacture($user);
    }

    private function avecApplication(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => encrypt(app(Google2FA::class)->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-de-secours-1'])),
            'two_factor_confirmed_at' => now(),
        ])->save();
    }

    private function evenements(User $user): array
    {
        $evenements = [];
        Notification::assertSentTo($user, TwoFactorNoticeNotification::class, function (TwoFactorNoticeNotification $n) use (&$evenements) {
            $evenements[] = $n->event;

            return true;
        });

        return $evenements;
    }

    // --- La règle ----------------------------------------------------------------

    public function test_expose_veut_dire_iban_enregistre_et_facture_emise(): void
    {
        $politique = app(TwoFactorPolicy::class);

        $rien = $this->compte();
        $ibanSeul = $this->compte();
        $this->avecIban($ibanSeul);
        $factureSeule = $this->compte();
        $this->avecFacture($factureSeule);
        $lesDeux = $this->compte();
        $this->exposer($lesDeux);
        $brouillon = $this->compte();
        $this->avecIban($brouillon);
        Invoice::factory()->create(['user_id' => $brouillon->id, 'client_id' => Client::factory()->create(['user_id' => $brouillon->id])->id]);

        $this->assertFalse($politique->isExposed($rien));
        $this->assertFalse($politique->isExposed($ibanSeul));
        $this->assertFalse($politique->isExposed($factureSeule));
        $this->assertFalse($politique->isExposed($brouillon), 'Une facture en brouillon n\'est pas émise.');
        $this->assertTrue($politique->isExposed($lesDeux));
    }

    public function test_la_regle_ne_depend_pas_du_compte_connecte(): void
    {
        // La portée multi-tenant filtre sur le compte connecté : la règle doit
        // lire le compte examiné, pas celui qui regarde.
        $expose = $this->compte();
        $this->exposer($expose);
        $this->actingAs($this->compte());

        $this->assertTrue(app(TwoFactorPolicy::class)->isExposed($expose));
    }

    // --- Le parcours -------------------------------------------------------------

    public function test_un_compte_expose_sans_second_facteur_recoit_un_preavis_de_quatorze_jours(): void
    {
        $user = $this->compte();
        $this->exposer($user);

        $this->artisan('security:enforce-two-factor')->assertSuccessful()->expectsOutputToContain('1 préavis');

        $user->refresh();
        $this->assertNotNull($user->two_factor_deadline_at);
        $this->assertEqualsWithDelta(now()->addDays(TwoFactorPolicy::GRACE_DAYS)->timestamp, $user->two_factor_deadline_at->timestamp, 5);
        $this->assertSame(['deadline'], $this->evenements($user));
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => AuditLog::ACTION_2FA_DEADLINE_SET]);

        // Rejouer le même jour ne fait rien.
        $this->artisan('security:enforce-two-factor')->expectsOutputToContain('0 préavis');
        Notification::assertSentToTimes($user, TwoFactorNoticeNotification::class, 1);
    }

    public function test_l_interrupteur_de_lancement_retient_tout_sauf_la_simulation(): void
    {
        config(['security.enforce_two_factor' => false]);
        $user = $this->compte();
        $this->exposer($user);

        $this->artisan('security:enforce-two-factor')->assertSuccessful()->expectsOutputToContain('Parcours désactivé');
        $this->assertNull($user->fresh()->two_factor_deadline_at);
        Notification::assertNothingSent();

        $this->artisan('security:enforce-two-factor --dry-run')->expectsOutputToContain('1 préavis');
        $this->assertNull($user->fresh()->two_factor_deadline_at);
    }

    public function test_un_compte_non_expose_ou_deja_protege_n_est_pas_touche(): void
    {
        $sansIban = $this->compte();
        $this->avecFacture($sansIban);
        $sansFacture = $this->compte();
        $this->avecIban($sansFacture);
        $avecEmail = $this->compte(['email_otp_enabled_at' => now()]);
        $this->exposer($avecEmail);
        $avecApp = $this->compte();
        $this->avecApplication($avecApp);
        $this->exposer($avecApp);
        $gele = $this->compte(['security_locked_at' => now()]);
        $this->exposer($gele);

        $this->artisan('security:enforce-two-factor')->expectsOutputToContain('0 préavis');

        foreach ([$sansIban, $sansFacture, $avecEmail, $avecApp, $gele] as $compte) {
            $this->assertNull($compte->fresh()->two_factor_deadline_at, $compte->email);
        }
        Notification::assertNothingSent();
    }

    public function test_le_rappel_part_deux_jours_avant_l_echeance_une_seule_fois(): void
    {
        $user = $this->compte();
        $this->exposer($user);
        $this->artisan('security:enforce-two-factor');

        $this->travel(11)->days();
        $this->artisan('security:enforce-two-factor');
        Notification::assertSentToTimes($user, TwoFactorNoticeNotification::class, 1);

        $this->travel(1)->days();
        $this->artisan('security:enforce-two-factor')->expectsOutputToContain('1 rappels');
        $this->assertSame(['deadline', 'reminder'], $this->evenements($user));
        $this->assertNotNull($user->fresh()->two_factor_reminded_at);
        $this->assertNull($user->fresh()->email_otp_enabled_at);

        $this->travel(1)->days();
        $this->artisan('security:enforce-two-factor');
        Notification::assertSentToTimes($user, TwoFactorNoticeNotification::class, 2);
    }

    public function test_a_l_echeance_le_code_par_email_s_active_tout_seul(): void
    {
        $user = $this->compte();
        $this->exposer($user);
        $this->artisan('security:enforce-two-factor');

        $this->travel(TwoFactorPolicy::GRACE_DAYS)->days();
        $this->artisan('security:enforce-two-factor')->expectsOutputToContain('1 codes par e-mail activés');

        $user->refresh();
        $this->assertNotNull($user->email_otp_enabled_at);
        $this->assertSame(['deadline', 'enabled'], $this->evenements($user));
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => AuditLog::ACTION_EMAIL_OTP_AUTO_ENABLED]);

        // Rejouer ne renvoie rien, et la connexion demande désormais un code.
        $this->artisan('security:enforce-two-factor');
        Notification::assertSentToTimes($user, TwoFactorNoticeNotification::class, 2);
        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD])->assertRedirect(route('two-factor.email'));
        $this->assertGuest();
    }

    public function test_celui_qui_choisit_l_application_avant_l_echeance_n_est_plus_concerne(): void
    {
        $user = $this->compte();
        $this->exposer($user);
        $this->artisan('security:enforce-two-factor');
        $this->avecApplication($user);

        $this->travel(TwoFactorPolicy::GRACE_DAYS)->days();
        $this->artisan('security:enforce-two-factor');

        $this->assertNull($user->fresh()->email_otp_enabled_at);
        Notification::assertSentToTimes($user, TwoFactorNoticeNotification::class, 1);
    }

    public function test_la_simulation_n_ecrit_rien_et_n_envoie_rien(): void
    {
        $user = $this->compte();
        $this->exposer($user);

        // Une seule attente : les deux mots sont sur la même ligne, et chaque
        // attente consomme une ligne de sortie.
        $this->artisan('security:enforce-two-factor --dry-run')
            ->expectsOutputToContain('[simulation] 1 comptes examinés : 1 préavis');

        $this->assertNull($user->fresh()->two_factor_deadline_at);
        $this->assertDatabaseMissing('audit_logs', ['action' => AuditLog::ACTION_2FA_DEADLINE_SET]);
        Notification::assertNothingSent();
    }

    public function test_un_compte_qui_retire_son_iban_est_libere(): void
    {
        $user = $this->compte();
        $this->exposer($user);
        $this->artisan('security:enforce-two-factor');
        $this->assertNotNull($user->fresh()->two_factor_deadline_at);

        $reglages = BusinessSettings::withoutGlobalScopes()->where('user_id', $user->id)->first();
        $reglages->iban = '';
        $reglages->save();

        $this->artisan('security:enforce-two-factor')->expectsOutputToContain('1 échéances levées');
        $this->assertNull($user->fresh()->two_factor_deadline_at);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => AuditLog::ACTION_2FA_RELEASED]);
    }

    public function test_le_bandeau_n_apparait_qu_aux_comptes_exposes_en_preavis(): void
    {
        $user = $this->compte();
        $this->exposer($user);
        $this->actingAs($user);

        $this->get(route('dashboard'))->assertInertia(fn ($page) => $page->where('security.two_factor_notice', null));

        $this->artisan('security:enforce-two-factor');
        // actingAs() garde l'instance en mémoire ; une vraie requête relit le
        // compte en base, avec son échéance.
        $this->actingAs($user->fresh());
        $this->get(route('dashboard'))->assertInertia(fn ($page) => $page
            ->where('security.two_factor_notice.days_left', TwoFactorPolicy::GRACE_DAYS)
            ->has('security.two_factor_notice.deadline'));

        $user->fresh()->forceFill(['email_otp_enabled_at' => now()])->save();
        $this->actingAs($user->fresh());
        $this->get(route('dashboard'))->assertInertia(fn ($page) => $page->where('security.two_factor_notice', null));
    }

    // --- Le support ---------------------------------------------------------------

    public function test_le_support_remet_a_zero_et_un_compte_expose_repart_avec_le_code_par_email(): void
    {
        $user = $this->compte(['email_otp_enabled_at' => now()]);
        $this->avecApplication($user);
        $this->exposer($user);
        TrustedDevice::create(['user_id' => $user->id, 'token' => hash('sha256', 'jeton'), 'expires_at' => now()->addDays(30)]);

        $this->artisan('security:reset-two-factor', ['email' => $user->email])
            ->assertSuccessful()
            ->expectsOutputToContain('code par e-mail réactivé');

        $user->refresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertNotNull($user->email_otp_enabled_at);
        $this->assertDatabaseCount('trusted_devices', 0);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => AuditLog::ACTION_2FA_RESET_BY_SUPPORT]);
    }

    public function test_le_support_peut_lever_le_second_facteur_quelques_heures(): void
    {
        $user = $this->compte(['email_otp_enabled_at' => now()]);
        $this->exposer($user);

        $this->artisan('security:reset-two-factor', ['email' => $user->email, '--lift-hours' => 48])
            ->expectsOutputToContain('sans second facteur jusqu');

        $user->refresh();
        $this->assertNull($user->email_otp_enabled_at);
        $this->assertEqualsWithDelta(now()->addHours(48)->timestamp, $user->two_factor_deadline_at->timestamp, 5);

        // Rien avant l'échéance, puis le code par e-mail revient tout seul.
        $this->artisan('security:enforce-two-factor');
        $this->assertNull($user->fresh()->email_otp_enabled_at);
        Notification::assertNothingSent();

        $this->travel(49)->hours();
        $this->artisan('security:enforce-two-factor');
        $this->assertNotNull($user->fresh()->email_otp_enabled_at);
    }

    public function test_un_compte_non_expose_remis_a_zero_reste_libre(): void
    {
        $user = $this->compte(['email_otp_enabled_at' => now()]);

        $this->artisan('security:reset-two-factor', ['email' => $user->email])->expectsOutputToContain('non exposé');

        $this->assertNull($user->fresh()->email_otp_enabled_at);
    }

    public function test_une_adresse_inconnue_est_refusee(): void
    {
        $this->artisan('security:reset-two-factor', ['email' => 'personne@example.lu'])->assertFailed();
    }
}
