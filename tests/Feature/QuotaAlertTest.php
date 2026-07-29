<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alerte de quota du tableau de bord : prévenir AVANT le blocage.
 */
class QuotaAlertTest extends TestCase
{
    use RefreshDatabase;

    private function freeUser(): User
    {
        // trial_ends_at passé => plan Gratuit (5 factures/mois, 10 clients)
        $user = User::factory()->create(['trial_ends_at' => now()->subMonth()]);
        $this->actingAs($user);

        return $user;
    }

    private function makeInvoices(User $user, int $count): void
    {
        $client = Client::factory()->create(['user_id' => $user->id]);
        for ($i = 0; $i < $count; $i++) {
            Invoice::factory()->create([
                'user_id' => $user->id,
                'client_id' => $client->id,
                'status' => Invoice::STATUS_DRAFT,
            ]);
        }
    }

    public function test_no_alert_while_usage_stays_low(): void
    {
        $user = $this->freeUser();
        $this->makeInvoices($user, 2); // 2/5 = 40 %

        $alerts = app(PlanService::class)->getQuotaAlerts($user);

        $this->assertSame([], array_values(array_filter(
            $alerts,
            fn ($a) => $a['type'] === 'invoices'
        )));
    }

    public function test_alert_appears_before_the_limit_is_hit(): void
    {
        $user = $this->freeUser();
        $this->makeInvoices($user, 4); // 4/5 = 80 % => on prévient

        $alert = collect(app(PlanService::class)->getQuotaAlerts($user))
            ->firstWhere('type', 'invoices');

        $this->assertNotNull($alert, 'Une alerte devait être remontée à 80 %.');
        $this->assertFalse($alert['reached']);
        $this->assertSame(1, $alert['remaining']);
    }

    public function test_alert_is_flagged_as_reached_at_the_limit(): void
    {
        $user = $this->freeUser();
        $this->makeInvoices($user, 5); // 5/5

        $alert = collect(app(PlanService::class)->getQuotaAlerts($user))
            ->firstWhere('type', 'invoices');

        $this->assertNotNull($alert);
        $this->assertTrue($alert['reached']);
        $this->assertSame(0, $alert['remaining']);
    }

    public function test_dashboard_exposes_the_alerts(): void
    {
        $user = $this->freeUser();
        $this->makeInvoices($user, 5);

        $this->get(route('dashboard', ['locale' => 'fr']))
            ->assertInertia(fn ($page) => $page->has('quotaAlerts'));
    }
}
