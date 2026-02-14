<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Authenticate user to create data so BelongsToUser trait assigns correct user_id
        $this->actingAs($this->user);

        BusinessSettings::factory()->create([
            'vat_regime' => 'assujetti',
        ]);

        $this->client = Client::factory()->create([
            'name' => 'Test Client',
            'default_hourly_rate' => 100,
        ]);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        auth()->logout();

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_user_can_access_dashboard(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('kpis')
                ->has('revenueChart')
                ->has('unpaidInvoices')
                ->has('unbilledTimeByClient')
                ->has('recentInvoices')
                ->has('availableYears')
                ->has('selectedYear')
                ->has('franchiseAlert')
            );
    }

    public function test_dashboard_shows_correct_kpis(): void
    {
        // Create some data
        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => 5000,
            'total_vat' => 850,
            'total_ttc' => 5850,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        Expense::factory()->create([
            'date' => now(),
            'amount_ht' => 1000,
            'vat_rate' => 17,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk();

        $kpis = $response->original->getData()['page']['props']['kpis'];
        $this->assertEquals(5000, $kpis['annual_revenue']);
        $this->assertEquals(1000, $kpis['annual_expenses']);
        $this->assertEquals(4000, $kpis['net_profit']);
        $this->assertEquals(35000, $kpis['vat_franchise_threshold']);
        $this->assertEquals(100000, $kpis['simplified_accounting_threshold']);
    }

    public function test_dashboard_can_filter_by_year(): void
    {
        $currentYear = now()->year;
        $lastYear = $currentYear - 1;

        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now()->setYear($currentYear),
            'total_ht' => 5000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now()->setYear($lastYear),
            'total_ht' => 3000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        // Test current year
        $response = $this->actingAs($this->user)
            ->get(route('dashboard', ['year' => $currentYear]))
            ->assertOk();

        $kpis = $response->original->getData()['page']['props']['kpis'];
        $this->assertEquals($currentYear, $response->original->getData()['page']['props']['selectedYear']);
        $this->assertEquals(5000, $kpis['annual_revenue']);

        // Test last year
        $response = $this->actingAs($this->user)
            ->get(route('dashboard', ['year' => $lastYear]))
            ->assertOk();

        $kpis = $response->original->getData()['page']['props']['kpis'];
        $this->assertEquals($lastYear, $response->original->getData()['page']['props']['selectedYear']);
        $this->assertEquals(3000, $kpis['annual_revenue']);
    }

    public function test_dashboard_shows_unpaid_invoices(): void
    {
        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_SENT,
            'number' => '2026-001',
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
            'total_ttc' => 1170,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('unpaidInvoices', 1)
                ->where('unpaidInvoices.0.number', '2026-001')
                ->where('kpis.unpaid_invoices.count', 1)
            );
    }

    public function test_dashboard_shows_unbilled_time(): void
    {
        TimeEntry::factory()->create([
            'client_id' => $this->client->id,
            'started_at' => now()->subHours(2),
            'stopped_at' => now(),
            'duration_seconds' => 7200,
            'is_billed' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk();

        $unbilledTimeByClient = $response->original->getData()['page']['props']['unbilledTimeByClient'];
        $this->assertCount(1, $unbilledTimeByClient);
        $this->assertEquals('Test Client', $unbilledTimeByClient[0]['client_name']);
        $this->assertEquals(2, $unbilledTimeByClient[0]['hours']);
    }

    public function test_dashboard_shows_revenue_chart(): void
    {
        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now()->setMonth(1),
            'total_ht' => 1000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk();

        $revenueChart = $response->original->getData()['page']['props']['revenueChart'];
        $this->assertCount(12, $revenueChart);
        $this->assertEquals('Jan', $revenueChart[0]['label']);
        $this->assertEquals(1000, $revenueChart[0]['revenue']);
    }

    public function test_dashboard_shows_alerts_when_threshold_exceeded(): void
    {
        // Create invoices that exceed VAT threshold
        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => 40000, // > 35000
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('kpis.alerts', 1)
                ->where('kpis.alerts.0.type', 'vat_threshold_exceeded')
                ->where('kpis.alerts.0.level', 'critical')
            );
    }

    public function test_dashboard_shows_recent_invoices(): void
    {
        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'number' => '2026-001',
            'issued_at' => now(),
            'total_ttc' => 1170,
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('recentInvoices', 1)
                ->where('recentInvoices.0.number', '2026-001')
            );
    }

    // API Endpoint Tests

    public function test_guest_cannot_access_kpis_api(): void
    {
        auth()->logout();

        $this->getJson(route('dashboard.kpis'))
            ->assertUnauthorized();
    }

    public function test_user_can_get_kpis_via_api(): void
    {
        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => 5000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('dashboard.kpis'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'annual_revenue',
                    'annual_expenses',
                    'net_profit',
                    'vat_franchise_threshold',
                    'vat_franchise_progress',
                    'unpaid_invoices',
                    'unbilled_time',
                    'vat_summary',
                    'alerts',
                ],
            ]);

        $this->assertEquals(5000, $response->json('data.annual_revenue'));
    }

    public function test_user_can_get_revenue_chart_via_api(): void
    {
        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now()->setMonth(6),
            'total_ht' => 2000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('dashboard.revenue-chart'))
            ->assertOk()
            ->assertJsonCount(12, 'data');

        $this->assertEquals(2000, $response->json('data.5.revenue')); // June (0-indexed)
    }

    public function test_user_can_get_unpaid_invoices_via_api(): void
    {
        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_SENT,
            'number' => '2026-001',
            'total_ttc' => 1170,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $this->actingAs($this->user)
            ->getJson(route('dashboard.unpaid-invoices'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.number', '2026-001');
    }

    public function test_user_can_get_unbilled_time_via_api(): void
    {
        TimeEntry::factory()->create([
            'client_id' => $this->client->id,
            'started_at' => now()->subHours(2),
            'stopped_at' => now(),
            'duration_seconds' => 7200,
            'is_billed' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('dashboard.unbilled-time'))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertEquals('Test Client', $response->json('data.0.client_name'));
        $this->assertEquals(2, $response->json('data.0.hours'));
    }

    public function test_user_can_get_vat_summary_via_api(): void
    {
        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_vat' => 170,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        // Create expense with 17% VAT rate
        Expense::factory()->create([
            'date' => now(),
            'amount_ht' => 294.12,
            'vat_rate' => 17,
            'is_deductible' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('dashboard.vat-summary'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'collected',
                    'deductible',
                    'balance',
                    'to_pay',
                    'credit',
                ],
            ]);

        $this->assertEquals(170, $response->json('data.collected'));
    }

    public function test_api_endpoints_can_filter_by_year(): void
    {
        $currentYear = now()->year;
        $lastYear = $currentYear - 1;

        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now()->setYear($currentYear),
            'total_ht' => 5000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now()->setYear($lastYear),
            'total_ht' => 3000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        // Current year
        $response = $this->actingAs($this->user)
            ->getJson(route('dashboard.kpis', ['year' => $currentYear]))
            ->assertOk();
        $this->assertEquals(5000, $response->json('data.annual_revenue'));

        // Last year
        $response = $this->actingAs($this->user)
            ->getJson(route('dashboard.kpis', ['year' => $lastYear]))
            ->assertOk();
        $this->assertEquals(3000, $response->json('data.annual_revenue'));
    }

    public function test_unpaid_invoices_api_respects_limit(): void
    {
        // Create 10 unpaid invoices
        for ($i = 1; $i <= 10; $i++) {
            Invoice::factory()->create([
                'client_id' => $this->client->id,
                'status' => Invoice::STATUS_SENT,
                'number' => "2026-00{$i}",
                'type' => Invoice::TYPE_INVOICE,
            ]);
        }

        $this->actingAs($this->user)
            ->getJson(route('dashboard.unpaid-invoices', ['limit' => 5]))
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_unbilled_time_api_respects_limit(): void
    {
        // Create 10 clients with unbilled time
        for ($i = 1; $i <= 10; $i++) {
            $client = Client::factory()->create();
            TimeEntry::factory()->create([
                'client_id' => $client->id,
                'started_at' => now()->subHours(1),
                'stopped_at' => now(),
                'duration_seconds' => 3600,
                'is_billed' => false,
            ]);
        }

        $this->actingAs($this->user)
            ->getJson(route('dashboard.unbilled-time', ['limit' => 5]))
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    // Franchise Alert Tests

    public function test_dashboard_includes_franchise_alert_data(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('franchiseAlert')
                ->has('franchiseAlert.show')
                ->has('franchiseAlert.status')
                ->has('franchiseAlert.yearly_revenue')
                ->has('franchiseAlert.threshold')
                ->has('franchiseAlert.country_code')
            );
    }

    public function test_dashboard_franchise_alert_shows_warning_when_approaching_threshold(): void
    {
        // Re-authenticate to access scoped models
        $this->actingAs($this->user);

        // Update settings to franchise regime
        BusinessSettings::first()->update([
            'vat_regime' => 'franchise',
            'country_code' => 'LU',
        ]);

        // Create invoices totaling 32000 EUR (>90% of 35000)
        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => 32000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('franchiseAlert.show', true)
                ->where('franchiseAlert.status', 'warning')
            );
    }

    public function test_dashboard_franchise_alert_shows_exceeded_when_threshold_passed(): void
    {
        // Re-authenticate to access scoped models
        $this->actingAs($this->user);

        // Update settings to franchise regime
        BusinessSettings::first()->update([
            'vat_regime' => 'franchise',
            'country_code' => 'LU',
        ]);

        // Create invoices totaling 40000 EUR (>35000 threshold)
        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => 40000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('franchiseAlert.show', true)
                ->where('franchiseAlert.status', 'exceeded')
            );
    }

    public function test_dashboard_franchise_alert_hidden_for_assujetti(): void
    {
        // Re-authenticate to access scoped models
        $this->actingAs($this->user);

        // Settings are already assujetti from setUp
        // Create high revenue that would trigger alert for franchise
        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => 50000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('franchiseAlert.show', false)
            );
    }
}
