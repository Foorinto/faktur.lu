<?php

namespace Tests\Feature;

use App\Models\EmailSettings;
use App\Models\Plan;
use App\Models\User;
use App\Services\EmailProviderService;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Le fournisseur d'e-mail personnel est réservé au plan Pro (FEAT-131).
 *
 * Un compte Gratuit ou Essentiel est renvoyé vers l'abonnement sur les
 * routes du fournisseur, et ses envois repassent par la plateforme même si
 * une configuration subsiste de l'essai. L'essai donne les fonctionnalités
 * Pro : rien ne change pendant l'essai.
 */
class CustomEmailProviderPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlansSeeder::class);
    }

    private function freeUser(): User
    {
        return User::factory()->create(['email_verified_at' => now(), 'trial_ends_at' => null]);
    }

    private function trialUser(): User
    {
        return User::factory()->create(['email_verified_at' => now(), 'trial_ends_at' => now()->addDays(10)]);
    }

    private function subscribedUser(string $plan): User
    {
        $user = $this->freeUser();
        $target = $plan === 'pro' ? Plan::pro() : Plan::essentiel();
        $target->update(['stripe_price_id_monthly' => "price_test_{$plan}"]);

        $subscription = $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => "sub_test_{$plan}_{$user->id}",
            'stripe_status' => 'active',
            'stripe_price' => "price_test_{$plan}",
            'quantity' => 1,
        ]);
        $subscription->items()->create([
            'stripe_id' => "si_test_{$plan}_{$user->id}",
            'stripe_product' => "prod_test_{$plan}",
            'stripe_price' => "price_test_{$plan}",
            'quantity' => 1,
        ]);

        return $user->fresh();
    }

    private function withResend(User $user): User
    {
        $this->actingAs($user);
        $settings = EmailSettings::getOrCreate($user);
        $settings->provider = EmailSettings::PROVIDER_RESEND;
        $settings->provider_config = ['api_key' => 're_secret'];
        $settings->save();

        return $user->fresh();
    }

    public function test_la_fonctionnalite_est_sur_pro_seulement(): void
    {
        $this->assertContains('custom_email_provider', Plan::pro()->features);
        $this->assertNotContains('custom_email_provider', Plan::essentiel()->features);
        $this->assertNotContains('custom_email_provider', Plan::free()->features);
    }

    public function test_un_compte_gratuit_ou_essentiel_est_renvoye_vers_l_abonnement_sur_les_quatre_routes(): void
    {
        $this->actingAs($this->subscribedUser('essentiel'))
            ->get(route('settings.email.provider'))
            ->assertRedirect(route('subscription.index'));

        $this->actingAs($this->freeUser());

        $this->get(route('settings.email.provider'))
            ->assertRedirect(route('subscription.index'))
            ->assertSessionHas('upgrade_required', fn ($u) => $u['feature'] === 'custom_email_provider' && $u['min_plan'] === 'Pro');
        $this->put(route('settings.email.provider.update'), ['provider' => 'resend', 'config' => ['api_key' => 'x']])
            ->assertRedirect(route('subscription.index'));
        $this->post(route('settings.email.provider.test'))
            ->assertRedirect(route('subscription.index'));
        $this->post(route('settings.email.provider.validate-smtp'), [])
            ->assertRedirect(route('subscription.index'));
    }

    public function test_un_compte_en_essai_ou_pro_accede_a_la_page(): void
    {
        foreach ([$this->trialUser(), $this->subscribedUser('pro')] as $user) {
            $this->actingAs($user)->get(route('settings.email.provider'))->assertOk();
        }
    }

    public function test_un_compte_gratuit_ou_essentiel_avec_un_fournisseur_configure_envoie_par_la_plateforme(): void
    {
        foreach ([$this->freeUser(), $this->subscribedUser('essentiel')] as $user) {
            $user = $this->withResend($user);
            $service = app(EmailProviderService::class);

            $this->assertFalse($service->customProviderAllowed($user), "compte {$user->id}");
            $this->assertSame(Mail::mailer(), $service->getMailerForUser($user));
            $this->assertNull(Config::get("mail.mailers.user_resend_{$user->id}"));
            // La configuration est conservée pour un passage en Pro.
            $this->assertSame('resend', $user->emailSettings->provider);
        }
    }

    public function test_un_compte_en_essai_ou_pro_envoie_par_son_fournisseur(): void
    {
        foreach ([$this->trialUser(), $this->subscribedUser('pro')] as $user) {
            $user = $this->withResend($user);
            $service = app(EmailProviderService::class);

            $this->assertTrue($service->customProviderAllowed($user), "compte {$user->id}");
            $service->getMailerForUser($user);
            $this->assertSame('smtp.resend.com', Config::get("mail.mailers.user_resend_{$user->id}.host"), "compte {$user->id}");
        }
    }
}
