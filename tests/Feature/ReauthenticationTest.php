<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\User;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Réauthentification à l'acte.
 *
 * Une session ouverte n'est pas une preuve d'identité. Avant de changer
 * l'IBAN, le QR de paiement, l'adresse email ou le mot de passe, et avant
 * d'exporter les données, on redemande le mot de passe, plus le code 2FA si
 * elle est active. Ce qui est protégé ici : chaque porte demande bien la même
 * chose, un formulaire qui ne touche pas à l'IBAN ne demande rien, et la
 * route de confirmation que Fortify enregistre de son côté n'offre pas de
 * passage au mot de passe seul.
 */
class ReauthenticationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlansSeeder::class);

        $this->user = User::factory()->create([
            'password' => 'password',
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
        $this->actingAs($this->user);
    }

    // --- Outils ----------------------------------------------------------------

    private function activerLa2FA(): void
    {
        $this->post('/user/confirm-password', ['password' => 'password']);
        $this->post('/user/two-factor-authentication');
        $this->user->refresh();

        $this->post('/user/confirmed-two-factor-authentication', ['code' => $this->codeValide()]);
        $this->user->refresh();

        // La confirmation posée pour l'enrôlement ne doit pas servir aux tests
        // qui suivent : on la retire.
        session()->forget('auth.password_confirmed_at');

        $this->assertTrue($this->user->hasEnabledTwoFactorAuthentication());
    }

    private function codeValide(): string
    {
        $code = app(Google2FA::class)->getCurrentOtp(decrypt($this->user->fresh()->two_factor_secret));

        // Fortify refuse un code déjà utilisé (protection contre le rejeu),
        // et ces tests réutilisent celui de l'enrôlement dans la même
        // demi-minute. On oublie la trace, le rejeu n'est pas ce qu'on teste.
        Cache::forget('fortify.2fa_codes.'.md5($code));

        return $code;
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

    private function reglagesEnPlace(): BusinessSettings
    {
        return BusinessSettings::factory()->create([
            'user_id' => $this->user->id,
            'iban' => 'LU280019400644750000',
        ]);
    }

    // --- L'IBAN ----------------------------------------------------------------

    public function test_changer_l_iban_exige_le_mot_de_passe(): void
    {
        $this->reglagesEnPlace();

        $this->put(route('settings.business.update'), $this->reglages(['iban' => 'LU120010001234567891']))
            ->assertSessionHasErrors('current_password');

        $this->assertSame('LU280019400644750000', BusinessSettings::first()->iban);
    }

    public function test_changer_l_iban_avec_le_mot_de_passe_passe(): void
    {
        $this->reglagesEnPlace();

        $this->put(route('settings.business.update'), $this->reglages([
            'iban' => 'LU120010001234567891',
            'current_password' => 'password',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('LU120010001234567891', BusinessSettings::first()->iban);
    }

    public function test_un_mauvais_mot_de_passe_ne_change_pas_l_iban(): void
    {
        $this->reglagesEnPlace();

        $this->put(route('settings.business.update'), $this->reglages([
            'iban' => 'LU120010001234567891',
            'current_password' => 'pas-le-bon',
        ]))->assertSessionHasErrors('current_password');

        $this->assertSame('LU280019400644750000', BusinessSettings::first()->iban);
    }

    public function test_les_autres_reglages_ne_demandent_rien(): void
    {
        // Retoucher le nom ou une couleur ne doit jamais demander le mot de
        // passe : sinon la protection devient une gêne, et on la contourne.
        $this->reglagesEnPlace();

        $this->put(route('settings.business.update'), $this->reglages(['company_name' => 'Nouveau nom']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Nouveau nom', BusinessSettings::first()->company_name);
    }

    public function test_l_iban_ecrit_avec_des_espaces_n_est_pas_un_changement(): void
    {
        $this->reglagesEnPlace();

        $this->put(route('settings.business.update'), $this->reglages(['iban' => 'lu28 0019 4006 4475 0000']))
            ->assertSessionHasNoErrors();
    }

    public function test_le_premier_iban_d_un_compte_exige_aussi_le_mot_de_passe(): void
    {
        // Un compte sans IBAN où l'on en pose un : c'est le détournement sur
        // des factures encore vierges.
        $this->put(route('settings.business.update'), $this->reglages())
            ->assertSessionHasErrors('current_password');

        $this->assertNull(BusinessSettings::first());
    }

    public function test_avec_la_2fa_le_mot_de_passe_seul_ne_suffit_plus(): void
    {
        $this->reglagesEnPlace();
        $this->activerLa2FA();

        $this->put(route('settings.business.update'), $this->reglages([
            'iban' => 'LU120010001234567891',
            'current_password' => 'password',
        ]))->assertSessionHasErrors('two_factor_code');

        $this->assertSame('LU280019400644750000', BusinessSettings::first()->iban);
    }

    public function test_avec_la_2fa_un_faux_code_est_refuse(): void
    {
        $this->reglagesEnPlace();
        $this->activerLa2FA();

        $this->put(route('settings.business.update'), $this->reglages([
            'iban' => 'LU120010001234567891',
            'current_password' => 'password',
            'two_factor_code' => '000000',
        ]))->assertSessionHasErrors('two_factor_code');

        $this->assertSame('LU280019400644750000', BusinessSettings::first()->iban);
    }

    public function test_avec_la_2fa_le_bon_code_passe(): void
    {
        $this->reglagesEnPlace();
        $this->activerLa2FA();

        $this->put(route('settings.business.update'), $this->reglages([
            'iban' => 'LU120010001234567891',
            'current_password' => 'password',
            'two_factor_code' => $this->codeValide(),
        ]))->assertSessionHasNoErrors();

        $this->assertSame('LU120010001234567891', BusinessSettings::first()->iban);
    }

    public function test_un_code_de_secours_passe_et_se_consomme(): void
    {
        // Sans son téléphone, l'utilisateur garde une porte. Mais elle ne
        // sert qu'une fois, comme à la connexion.
        $this->reglagesEnPlace();
        $this->activerLa2FA();

        $codes = $this->user->fresh()->recoveryCodes();
        $secours = $codes[0];

        $this->put(route('settings.business.update'), $this->reglages([
            'iban' => 'LU120010001234567891',
            'current_password' => 'password',
            'two_factor_code' => $secours,
        ]))->assertSessionHasNoErrors();

        $this->assertSame('LU120010001234567891', BusinessSettings::first()->iban);
        $this->assertNotContains($secours, $this->user->fresh()->recoveryCodes());
        $this->assertCount(count($codes), $this->user->fresh()->recoveryCodes());
    }

    public function test_les_essais_sont_limites(): void
    {
        // Six chiffres se devinent en quelques minutes sans frein.
        $this->reglagesEnPlace();

        foreach (range(1, 5) as $i) {
            $this->put(route('settings.business.update'), $this->reglages([
                'iban' => 'LU120010001234567891',
                'current_password' => 'faux-'.$i,
            ]));
        }

        // Le sixième essai est refusé même avec le bon mot de passe.
        $this->put(route('settings.business.update'), $this->reglages([
            'iban' => 'LU120010001234567891',
            'current_password' => 'password',
        ]))->assertSessionHasErrors('current_password');

        $this->assertSame('LU280019400644750000', BusinessSettings::first()->iban);
    }

    // --- Le QR de paiement ----------------------------------------------------

    public function test_remplacer_le_qr_de_paiement_exige_le_mot_de_passe(): void
    {
        Storage::fake('public');
        $this->reglagesEnPlace();

        $this->post(route('settings.business.payment-qrcode.upload'), [
            'payment_qrcode' => UploadedFile::fake()->image('qr.png', 200, 200),
        ])->assertSessionHasErrors('current_password');

        $this->assertNull(BusinessSettings::first()->payment_qrcode_path);

        $this->post(route('settings.business.payment-qrcode.upload'), [
            'payment_qrcode' => UploadedFile::fake()->image('qr.png', 200, 200),
            'current_password' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertNotNull(BusinessSettings::first()->payment_qrcode_path);
    }

    // --- L'email et le mot de passe -------------------------------------------

    public function test_changer_l_email_avec_la_2fa_exige_le_code(): void
    {
        $this->activerLa2FA();

        $this->patch(route('profile.update'), [
            'name' => $this->user->name,
            'email' => 'nouvelle@example.lu',
            'current_password' => 'password',
        ])->assertSessionHasErrors('two_factor_code');

        $this->assertNotSame('nouvelle@example.lu', $this->user->fresh()->email);

        $this->patch(route('profile.update'), [
            'name' => $this->user->name,
            'email' => 'nouvelle@example.lu',
            'current_password' => 'password',
            'two_factor_code' => $this->codeValide(),
        ])->assertSessionHasNoErrors();

        $this->assertSame('nouvelle@example.lu', $this->user->fresh()->email);
    }

    public function test_changer_le_nom_ne_demande_toujours_rien(): void
    {
        $this->activerLa2FA();

        $this->patch(route('profile.update'), [
            'name' => 'Nouveau nom',
            'email' => $this->user->email,
        ])->assertSessionHasNoErrors();
    }

    public function test_changer_le_mot_de_passe_avec_la_2fa_exige_le_code(): void
    {
        $this->activerLa2FA();

        $this->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'Un-nouveau-mot-de-passe-long-42',
            'password_confirmation' => 'Un-nouveau-mot-de-passe-long-42',
        ])->assertSessionHasErrors('two_factor_code');

        $this->put(route('password.update'), [
            'current_password' => 'password',
            'two_factor_code' => $this->codeValide(),
            'password' => 'Un-nouveau-mot-de-passe-long-42',
            'password_confirmation' => 'Un-nouveau-mot-de-passe-long-42',
        ])->assertSessionHasNoErrors();
    }

    // --- La page de confirmation, et celle de Fortify -------------------------

    public function test_la_page_de_confirmation_exige_le_code_quand_la_2fa_est_active(): void
    {
        $this->activerLa2FA();

        $this->post(route('password.confirm'), ['password' => 'password'])
            ->assertSessionHasErrors('two_factor_code');
        $this->assertNull(session('auth.password_confirmed_at'));

        $this->post(route('password.confirm'), ['password' => 'password', 'two_factor_code' => $this->codeValide()])
            ->assertSessionHasNoErrors();
        $this->assertNotNull(session('auth.password_confirmed_at'));
    }

    public function test_la_route_de_confirmation_de_fortify_n_est_pas_une_porte_derobee(): void
    {
        // ⚠️ Fortify enregistre sa propre route `user/confirm-password`, qui
        // pose la même clé de session. Sans le remplacement de son action,
        // le mot de passe seul y suffirait, et de là on désactiverait la 2FA.
        $this->activerLa2FA();

        $this->post('/user/confirm-password', ['password' => 'password'])
            ->assertSessionHasErrors('two_factor_code');
        $this->assertNull(session('auth.password_confirmed_at'));
    }

    public function test_desactiver_la_2fa_exige_une_confirmation_avec_le_code(): void
    {
        $this->activerLa2FA();

        // Sans confirmation récente, Fortify renvoie vers la page.
        $this->delete('/user/two-factor-authentication')->assertRedirect(route('password.confirm'));
        $this->assertTrue($this->user->fresh()->hasEnabledTwoFactorAuthentication());

        $this->post(route('password.confirm'), ['password' => 'password', 'two_factor_code' => $this->codeValide()]);
        $this->delete('/user/two-factor-authentication')->assertRedirect();

        $this->assertFalse($this->user->fresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_la_confirmation_expire_apres_dix_minutes(): void
    {
        $this->post(route('password.confirm'), ['password' => 'password']);

        $this->travel(9)->minutes();
        $this->get(route('exports.audit.index'))->assertOk();

        $this->travel(2)->minutes();
        $this->get(route('exports.audit.index'))->assertRedirect(route('password.confirm'));
    }

    // --- Les exports -----------------------------------------------------------

    public function test_les_exports_exigent_une_confirmation(): void
    {
        $this->get(route('exports.audit.index'))->assertRedirect(route('password.confirm'));
        $this->get(route('exports.accounting.index'))->assertRedirect(route('password.confirm'));
        $this->get(route('audit-logs.export'))->assertRedirect(route('password.confirm'));

        $this->post(route('password.confirm'), ['password' => 'password']);

        $this->get(route('exports.audit.index'))->assertOk();
        $this->get(route('exports.accounting.index'))->assertOk();
    }
}
