<?php

namespace Tests\Feature;

use App\Models\EmailSettings;
use App\Models\User;
use App\Services\EmailProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Envoi par le fournisseur d'e-mail de l'utilisateur (retour du 2026-09-24).
 *
 * Resend et Postmark passaient par des transports Laravel dont les
 * bibliothèques ne sont pas installées : « Class "Resend" not found », une
 * erreur PHP qui échappait au filet de l'envoi de test et finissait en code
 * de référence. Les deux passent désormais par le relais SMTP du fournisseur,
 * comme Brevo. Et l'expéditeur par défaut d'un fournisseur personnel est
 * l'adresse de l'utilisateur, pas celle de la plateforme.
 */
class EmailProviderRelayTest extends TestCase
{
    use RefreshDatabase;

    private function settingsFor(User $user, string $provider, array $config, ?string $from = null): EmailSettings
    {
        $this->actingAs($user);
        $settings = EmailSettings::getOrCreate($user);
        $settings->provider = $provider;
        $settings->provider_config = $config;
        $settings->from_address = $from;
        $settings->save();

        return $settings->fresh();
    }

    private function user(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    public function test_resend_passe_par_son_relais_smtp_avec_la_cle_api(): void
    {
        $user = $this->user();
        $settings = $this->settingsFor($user, EmailSettings::PROVIDER_RESEND, ['api_key' => 're_secret']);

        app(EmailProviderService::class)->getMailerForUser($user->fresh());

        $this->assertSame([
            'transport' => 'smtp',
            'host' => 'smtp.resend.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'resend',
            'password' => 're_secret',
            'timeout' => 30,
            'from' => ['address' => $user->email, 'name' => $settings->getEffectiveFromName()],
        ], Config::get("mail.mailers.user_resend_{$user->id}"));
    }

    public function test_postmark_passe_par_son_relais_smtp_avec_le_jeton_des_deux_cotes(): void
    {
        $user = $this->user();
        $this->settingsFor($user, EmailSettings::PROVIDER_POSTMARK, ['token' => 'pm_secret']);

        app(EmailProviderService::class)->getMailerForUser($user->fresh());

        $config = Config::get("mail.mailers.user_postmark_{$user->id}");
        $this->assertSame('smtp', $config['transport']);
        $this->assertSame('smtp.postmarkapp.com', $config['host']);
        $this->assertSame('pm_secret', $config['username']);
        $this->assertSame('pm_secret', $config['password']);
        $this->assertSame(587, $config['port']);
        $this->assertSame('tls', $config['encryption']);
    }

    public function test_brevo_garde_exactement_sa_configuration(): void
    {
        $user = $this->user();
        $this->settingsFor($user, EmailSettings::PROVIDER_BREVO, ['username' => 'moi@exemple.lu', 'api_key' => 'xsmtp'], 'moi@exemple.lu');

        app(EmailProviderService::class)->getMailerForUser($user->fresh());

        $this->assertSame([
            'transport' => 'smtp',
            'host' => 'smtp-relay.brevo.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'moi@exemple.lu',
            'password' => 'xsmtp',
            'timeout' => 30,
            'from' => ['address' => 'moi@exemple.lu', 'name' => EmailSettings::getOrCreate($user)->getEffectiveFromName()],
        ], Config::get("mail.mailers.user_brevo_{$user->id}"));
    }

    public function test_l_expediteur_par_defaut_est_l_adresse_de_l_utilisateur_avec_son_propre_fournisseur(): void
    {
        $user = $this->user();

        $perso = $this->settingsFor($user, EmailSettings::PROVIDER_RESEND, ['api_key' => 're_secret']);
        $this->assertSame($user->email, $perso->getEffectiveFromAddress());

        $explicite = $this->settingsFor($user, EmailSettings::PROVIDER_RESEND, ['api_key' => 're_secret'], 'factures@exemple.lu');
        $this->assertSame('factures@exemple.lu', $explicite->getEffectiveFromAddress());

        $plateforme = $this->settingsFor($user, EmailSettings::PROVIDER_FAKTUR, []);
        $this->assertSame(config('mail.from.address'), $plateforme->getEffectiveFromAddress());
    }

    public function test_une_erreur_php_a_l_envoi_de_test_donne_un_message_lisible_pas_un_code(): void
    {
        $user = $this->user();
        $settings = $this->settingsFor($user, EmailSettings::PROVIDER_RESEND, ['api_key' => 're_secret']);

        $this->partialMock(EmailProviderService::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('createResendMailer')
                ->andThrow(new \Error('Class "Resend" not found'));
        });

        $result = app(EmailProviderService::class)->testConfiguration($settings);

        $this->assertFalse($result['success']);
        $this->assertStringStartsWith('Échec de l\'envoi : ', $result['message']);
        $this->assertStringNotContainsString('réf', $result['message']);
        $this->assertFalse($settings->fresh()->provider_verified);
        $this->assertNotNull($settings->fresh()->last_test_at);
    }

    public function test_une_cle_refusee_par_le_relais_donne_authentification_echouee(): void
    {
        $user = $this->user();
        $settings = $this->settingsFor($user, EmailSettings::PROVIDER_RESEND, ['api_key' => 're_fausse']);

        // Message réel de Symfony sur smtp.resend.com avec une clé bidon (2026-09-24).
        $this->partialMock(EmailProviderService::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('createResendMailer')
                ->andThrow(new \RuntimeException('Failed to authenticate on SMTP server with username "resend" using the following authenticators: "LOGIN", "PLAIN".'));
        });

        $result = app(EmailProviderService::class)->testConfiguration($settings);

        $this->assertSame('Échec de l\'envoi : Authentification échouée. Vérifiez vos identifiants.', $result['message']);
    }

    public function test_un_expediteur_inconnu_du_fournisseur_est_expliqué(): void
    {
        $user = $this->user();
        $settings = $this->settingsFor($user, EmailSettings::PROVIDER_RESEND, ['api_key' => 're_secret']);

        $this->partialMock(EmailProviderService::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('createResendMailer')
                ->andThrow(new \RuntimeException('The gmail.com domain is not verified. Please, add and verify your domain on https://resend.com/domains'));
        });

        $result = app(EmailProviderService::class)->testConfiguration($settings);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Adresse d\'expédition inconnue de votre fournisseur', $result['message']);
    }
}
