<?php

namespace Tests\Feature;

use App\Jobs\SendPaymentReminders;
use App\Mail\ReminderMail;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\EmailSettings;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\User;
use App\Services\EmailProviderService;
use App\Services\PlanService;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Écarts entre le code et les plans annoncés, relevés le 2026-09-24.
 *
 * Relancer à la main est ouvert à tous ; les relances automatiques restent
 * Pro, et le job le vérifie. La mention faktur.lu disparaît dès Essentiel,
 * ce que le code faisait déjà : la clé de plan le dit maintenant. L'export
 * Peppol et le paramétrage comptable ont leur garde. « Reporting avancé »
 * n'existe plus nulle part.
 */
class PlanGapsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlansSeeder::class);
    }

    private function freeUser(): User
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'trial_ends_at' => null]);
        BusinessSettings::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function trialUser(): User
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'trial_ends_at' => now()->addDays(10)]);
        BusinessSettings::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function subscribedUser(string $plan): User
    {
        $user = $this->freeUser();
        $target = $plan === 'pro' ? Plan::pro() : Plan::essentiel();
        $target->update(['stripe_price_id_monthly' => "price_test_{$plan}"]);
        $subscription = $user->subscriptions()->create([
            'type' => 'default', 'stripe_id' => "sub_test_{$plan}_{$user->id}", 'stripe_status' => 'active',
            'stripe_price' => "price_test_{$plan}", 'quantity' => 1,
        ]);
        $subscription->items()->create([
            'stripe_id' => "si_test_{$plan}_{$user->id}", 'stripe_product' => "prod_test_{$plan}",
            'stripe_price' => "price_test_{$plan}", 'quantity' => 1,
        ]);

        return $user->fresh();
    }

    private function overdueInvoice(User $user): Invoice
    {
        $client = Client::factory()->create(['user_id' => $user->id, 'email' => 'client@exemple.lu']);

        return Invoice::factory()->create([
            'user_id' => $user->id, 'client_id' => $client->id, 'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now()->subDays(40), 'due_at' => now()->subDays(10),
        ]);
    }

    public function test_les_plans_ne_promettent_plus_de_reporting_avance_et_retirent_la_mention_des_essentiel(): void
    {
        foreach (['free', 'essentiel', 'pro'] as $plan) {
            $this->assertNotContains('advanced_reporting', Plan::where('name', $plan)->first()->features, $plan);
        }
        $this->assertNotContains('no_branding', Plan::free()->features);
        $this->assertContains('no_branding', Plan::essentiel()->features);
        $this->assertContains('no_branding', Plan::pro()->features);
    }

    public function test_un_compte_gratuit_relance_a_la_main(): void
    {
        Mail::fake();
        $user = $this->freeUser();
        $invoice = $this->overdueInvoice($user);

        $this->actingAs($user)->post(route('invoices.send-reminder', $invoice), [
            'level' => 1, 'recipient_email' => 'client@exemple.lu', 'subject' => 'Rappel', 'message' => 'Merci de régler la facture.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        Mail::assertSent(ReminderMail::class, 1);
    }

    public function test_le_job_ne_relance_pas_pour_un_compte_gratuit(): void
    {
        // Facture en retard sans aucune relance déjà envoyée : seul le plan
        // peut retenir le job.
        Mail::fake();
        $user = $this->freeUser();
        $this->overdueInvoice($user);
        EmailSettings::getOrCreate($user)->update(['reminders_enabled' => true]);

        app(SendPaymentReminders::class)->handle(app(EmailProviderService::class), app(PlanService::class));

        Mail::assertNothingSent();
    }

    public function test_le_job_relance_pour_un_compte_pro(): void
    {
        Mail::fake();
        $user = $this->subscribedUser('pro');
        $this->overdueInvoice($user);
        EmailSettings::getOrCreate($user)->update(['reminders_enabled' => true]);

        app(SendPaymentReminders::class)->handle(app(EmailProviderService::class), app(PlanService::class));

        Mail::assertSent(ReminderMail::class, 1);
    }

    public function test_la_mention_faktur_lu_suit_la_cle_de_plan(): void
    {
        $plans = app(PlanService::class);
        $this->assertFalse($plans->hasFeature($this->freeUser(), 'no_branding'));
        $this->assertTrue($plans->hasFeature($this->subscribedUser('essentiel'), 'no_branding'));
        $this->assertTrue($plans->hasFeature($this->subscribedUser('pro'), 'no_branding'));
        $this->assertTrue($plans->hasFeature($this->trialUser(), 'no_branding'));
    }

    public function test_l_export_peppol_et_le_parametrage_comptable_sont_gardes(): void
    {
        $free = $this->freeUser();
        $invoice = $this->overdueInvoice($free);
        $this->actingAs($free)->get(route('invoices.peppol', $invoice))->assertRedirect(route('subscription.index'));
        $this->actingAs($free)->get(route('settings.accounting.edit'))->assertRedirect(route('subscription.index'));

        $essentiel = $this->subscribedUser('essentiel');
        $this->actingAs($essentiel)->get(route('settings.accounting.edit'))->assertOk();
        $reponse = $this->actingAs($essentiel)->get(route('invoices.peppol', $this->overdueInvoice($essentiel)));
        $this->assertNotSame(route('subscription.index'), $reponse->headers->get('Location'), 'un compte Essentiel n\'est pas renvoyé vers l\'abonnement');
    }
}
