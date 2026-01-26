<?php

namespace Tests\Unit\Services;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\TimeEntry;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DashboardService();

        // Create business settings
        BusinessSettings::factory()->create([
            'vat_regime' => 'assujetti',
        ]);
    }

    public function test_vat_franchise_threshold_constant(): void
    {
        $this->assertEquals(35000, DashboardService::VAT_FRANCHISE_THRESHOLD);
    }

    public function test_simplified_accounting_threshold_constant(): void
    {
        $this->assertEquals(100000, DashboardService::SIMPLIFIED_ACCOUNTING_THRESHOLD);
    }

    public function test_get_annual_revenue_sums_finalized_invoices(): void
    {
        $client = Client::factory()->create();
        $year = now()->year;

        // Create finalized invoice for current year
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => 1000,
            'total_ttc' => 1170,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        // Create sent invoice for current year
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_SENT,
            'issued_at' => now(),
            'total_ht' => 2000,
            'total_ttc' => 2340,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        // Create paid invoice for current year
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_PAID,
            'issued_at' => now(),
            'total_ht' => 3000,
            'total_ttc' => 3510,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        // Draft should NOT be counted
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_DRAFT,
            'issued_at' => now(),
            'total_ht' => 5000,
        ]);

        $revenue = $this->service->getAnnualRevenue($year);

        $this->assertEquals(6000.0, $revenue);
    }

    public function test_get_annual_revenue_excludes_credit_notes(): void
    {
        $client = Client::factory()->create();
        $year = now()->year;

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => 1000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        // Credit note should NOT be counted
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => 500,
            'type' => Invoice::TYPE_CREDIT_NOTE,
        ]);

        $revenue = $this->service->getAnnualRevenue($year);

        $this->assertEquals(1000.0, $revenue);
    }

    public function test_get_annual_revenue_filters_by_year(): void
    {
        $client = Client::factory()->create();
        $currentYear = now()->year;
        $lastYear = $currentYear - 1;

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now()->setYear($currentYear),
            'total_ht' => 1000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now()->setYear($lastYear),
            'total_ht' => 2000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $this->assertEquals(1000.0, $this->service->getAnnualRevenue($currentYear));
        $this->assertEquals(2000.0, $this->service->getAnnualRevenue($lastYear));
    }

    public function test_get_annual_expenses(): void
    {
        $year = now()->year;

        Expense::factory()->create([
            'date' => now(),
            'amount_ht' => 500,
        ]);

        Expense::factory()->create([
            'date' => now(),
            'amount_ht' => 300,
        ]);

        $expenses = $this->service->getAnnualExpenses($year);

        $this->assertEquals(800.0, $expenses);
    }

    public function test_get_vat_franchise_progress_under_threshold(): void
    {
        $progress = $this->service->getVatFranchiseProgress(10000);

        $this->assertEquals(10000, $progress['current']);
        $this->assertEquals(35000, $progress['threshold']);
        $this->assertEquals(28.6, $progress['percentage']);
        $this->assertEquals(25000, $progress['remaining']);
    }

    public function test_get_vat_franchise_progress_at_threshold(): void
    {
        $progress = $this->service->getVatFranchiseProgress(35000);

        $this->assertEquals(100.0, $progress['percentage']);
        $this->assertEquals(0, $progress['remaining']);
    }

    public function test_get_vat_franchise_progress_over_threshold(): void
    {
        $progress = $this->service->getVatFranchiseProgress(50000);

        $this->assertEquals(100.0, $progress['percentage']); // Capped at 100
        $this->assertEquals(0, $progress['remaining']);
    }

    public function test_get_simplified_accounting_progress(): void
    {
        $progress = $this->service->getSimplifiedAccountingProgress(50000);

        $this->assertEquals(50000, $progress['current']);
        $this->assertEquals(100000, $progress['threshold']);
        $this->assertEquals(50.0, $progress['percentage']);
        $this->assertEquals(50000, $progress['remaining']);
    }

    public function test_get_unpaid_invoices_stats(): void
    {
        $client = Client::factory()->create();

        // Finalized but not paid
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'total_ttc' => 1000,
            'due_at' => now()->addDays(30),
            'type' => Invoice::TYPE_INVOICE,
        ]);

        // Sent but not paid (overdue)
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_SENT,
            'total_ttc' => 2000,
            'due_at' => now()->subDays(10),
            'type' => Invoice::TYPE_INVOICE,
        ]);

        // Paid - should NOT be counted
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_PAID,
            'total_ttc' => 3000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $stats = $this->service->getUnpaidInvoicesStats();

        $this->assertEquals(2, $stats['count']);
        $this->assertEquals(3000.0, $stats['total_amount']);
        $this->assertEquals(1, $stats['overdue_count']);
        $this->assertEquals(2000.0, $stats['overdue_amount']);
    }

    public function test_get_unpaid_invoices_list(): void
    {
        $client = Client::factory()->create(['name' => 'Test Client']);

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'number' => '2026-001',
            'total_ttc' => 1000,
            'due_at' => now()->addDays(30),
            'type' => Invoice::TYPE_INVOICE,
        ]);

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_SENT,
            'number' => '2026-002',
            'total_ttc' => 2000,
            'due_at' => now()->subDays(5),
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $invoices = $this->service->getUnpaidInvoices(10);

        $this->assertCount(2, $invoices);
        $this->assertEquals('2026-002', $invoices[0]['number']); // Overdue first (ordered by due_at)
        $this->assertTrue($invoices[0]['is_overdue']);
        $this->assertEquals(5, $invoices[0]['days_overdue']);
        $this->assertEquals('Test Client', $invoices[0]['client_name']);
    }

    public function test_get_unbilled_time_stats(): void
    {
        $client = Client::factory()->create();

        // Unbilled, stopped time entry
        $startedAt = now()->subHours(2);
        $stoppedAt = now();
        TimeEntry::factory()->create([
            'client_id' => $client->id,
            'started_at' => $startedAt,
            'stopped_at' => $stoppedAt,
            'duration_seconds' => $startedAt->diffInSeconds($stoppedAt),
            'is_billed' => false,
        ]);

        // Another unbilled entry
        $startedAt2 = now()->subHours(1);
        $stoppedAt2 = now();
        TimeEntry::factory()->create([
            'client_id' => $client->id,
            'started_at' => $startedAt2,
            'stopped_at' => $stoppedAt2,
            'duration_seconds' => $startedAt2->diffInSeconds($stoppedAt2),
            'is_billed' => false,
        ]);

        // Billed - should NOT be counted
        TimeEntry::factory()->create([
            'client_id' => $client->id,
            'started_at' => now()->subHours(3),
            'stopped_at' => now()->subHours(2),
            'duration_seconds' => 3600,
            'is_billed' => true,
        ]);

        $stats = $this->service->getUnbilledTimeStats();

        // 2 hours + 1 hour = 3 hours = 10800 seconds
        $this->assertEquals(10800, $stats['total_seconds']);
        $this->assertEquals(3.0, $stats['hours']);
    }

    public function test_get_unbilled_time_by_client(): void
    {
        $client1 = Client::factory()->create([
            'name' => 'Client A',
            'default_hourly_rate' => 100,
        ]);
        $client2 = Client::factory()->create([
            'name' => 'Client B',
            'default_hourly_rate' => 150,
        ]);

        // Client 1: 2 hours
        TimeEntry::factory()->create([
            'client_id' => $client1->id,
            'started_at' => now()->subHours(2),
            'stopped_at' => now(),
            'duration_seconds' => 7200,
            'is_billed' => false,
        ]);

        // Client 2: 3 hours
        TimeEntry::factory()->create([
            'client_id' => $client2->id,
            'started_at' => now()->subHours(3),
            'stopped_at' => now(),
            'duration_seconds' => 10800,
            'is_billed' => false,
        ]);

        $result = $this->service->getUnbilledTimeByClient(10);

        $this->assertCount(2, $result);
        // Ordered by total_seconds desc
        $this->assertEquals('Client B', $result[0]['client_name']);
        $this->assertEquals(10800, $result[0]['total_seconds']);
        $this->assertEquals(3.0, $result[0]['hours']);
        $this->assertEquals(450.0, $result[0]['estimated_amount']); // 3h * 150€
    }

    public function test_get_vat_summary(): void
    {
        $client = Client::factory()->create();
        $year = now()->year;

        // Invoice with VAT
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => 1000,
            'total_vat' => 170,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        // Expense with deductible VAT (17% VAT rate)
        Expense::factory()->create([
            'date' => now(),
            'amount_ht' => 500,
            'vat_rate' => 17, // Must specify vat_rate because model recalculates amount_vat
            'is_deductible' => true,
        ]);

        // Expense without deductible VAT
        Expense::factory()->create([
            'date' => now(),
            'amount_ht' => 200,
            'vat_rate' => 17,
            'is_deductible' => false,
        ]);

        $summary = $this->service->getVatSummary($year);

        $this->assertEquals(170.0, $summary['collected']);
        $this->assertEquals(85.0, $summary['deductible']); // 500 * 0.17 = 85
        $this->assertEquals(85.0, $summary['balance']); // 170 - 85
        $this->assertEquals(85.0, $summary['to_pay']);
        $this->assertEquals(0, $summary['credit']);
    }

    public function test_get_vat_summary_with_credit(): void
    {
        $client = Client::factory()->create();
        $year = now()->year;

        // Invoice with VAT
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => 100,
            'total_vat' => 17,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        // Expense with more deductible VAT (17% rate = 500 * 0.17 = 85)
        Expense::factory()->create([
            'date' => now(),
            'amount_ht' => 500,
            'vat_rate' => 17,
            'is_deductible' => true,
        ]);

        $summary = $this->service->getVatSummary($year);

        $this->assertEquals(-68.0, $summary['balance']); // 17 - 85
        $this->assertEquals(0, $summary['to_pay']);
        $this->assertEquals(68.0, $summary['credit']);
    }

    public function test_get_revenue_chart_returns_all_months(): void
    {
        $client = Client::factory()->create();
        $year = now()->year;

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now()->setMonth(3),
            'total_ht' => 1000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_PAID,
            'issued_at' => now()->setMonth(6),
            'total_ht' => 2000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $chart = $this->service->getRevenueChart($year);

        $this->assertCount(12, $chart);
        $this->assertEquals('Jan', $chart[0]['label']);
        $this->assertEquals('Déc', $chart[11]['label']);
        $this->assertEquals(1000.0, $chart[2]['revenue']); // March
        $this->assertEquals(2000.0, $chart[5]['revenue']); // June
        $this->assertEquals(0.0, $chart[0]['revenue']); // January (no data)
    }

    public function test_get_alerts_returns_vat_threshold_exceeded(): void
    {
        $alerts = $this->service->getAlerts(now()->year, 40000);

        $this->assertCount(1, $alerts);
        $this->assertEquals('vat_threshold_exceeded', $alerts[0]['type']);
        $this->assertEquals('critical', $alerts[0]['level']);
    }

    public function test_get_alerts_returns_vat_threshold_warning(): void
    {
        $alerts = $this->service->getAlerts(now()->year, 30000);

        $vatAlerts = array_filter($alerts, fn($a) => $a['type'] === 'vat_threshold_warning');
        $this->assertCount(1, $vatAlerts);
        $vatAlert = reset($vatAlerts);
        $this->assertEquals('warning', $vatAlert['level']);
    }

    public function test_get_alerts_returns_accounting_threshold_exceeded(): void
    {
        $alerts = $this->service->getAlerts(now()->year, 120000);

        $accountingAlerts = array_filter($alerts, fn($a) => $a['type'] === 'accounting_threshold_exceeded');
        $this->assertCount(1, $accountingAlerts);
    }

    public function test_get_alerts_returns_overdue_invoices_alert(): void
    {
        $client = Client::factory()->create();

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_SENT,
            'due_at' => now()->subDays(5),
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $alerts = $this->service->getAlerts(now()->year, 0);

        $overdueAlerts = array_filter($alerts, fn($a) => $a['type'] === 'overdue_invoices');
        $this->assertCount(1, $overdueAlerts);
        $overdueAlert = reset($overdueAlerts);
        $this->assertEquals('warning', $overdueAlert['level']);
    }

    public function test_get_recent_invoices(): void
    {
        $client = Client::factory()->create(['name' => 'Test Client']);

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'number' => '2026-001',
            'issued_at' => now()->subDays(2),
            'total_ttc' => 1170,
        ]);

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_PAID,
            'number' => '2026-002',
            'issued_at' => now()->subDays(1),
            'total_ttc' => 2340,
        ]);

        // Draft should NOT appear
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_DRAFT,
            'number' => '2026-003',
            'issued_at' => now(),
        ]);

        $invoices = $this->service->getRecentInvoices(10);

        $this->assertCount(2, $invoices);
        $this->assertEquals('2026-002', $invoices[0]['number']); // Most recent first
        $this->assertEquals('Test Client', $invoices[0]['client_name']);
        $this->assertEquals('paid', $invoices[0]['status']);
    }

    public function test_get_kpis_returns_complete_data(): void
    {
        $year = now()->year;
        $kpis = $this->service->getKpis($year);

        $this->assertArrayHasKey('annual_revenue', $kpis);
        $this->assertArrayHasKey('annual_revenue_change', $kpis);
        $this->assertArrayHasKey('annual_expenses', $kpis);
        $this->assertArrayHasKey('net_profit', $kpis);
        $this->assertArrayHasKey('net_profit_change', $kpis);
        $this->assertArrayHasKey('vat_franchise_threshold', $kpis);
        $this->assertArrayHasKey('vat_franchise_progress', $kpis);
        $this->assertArrayHasKey('simplified_accounting_threshold', $kpis);
        $this->assertArrayHasKey('simplified_accounting_progress', $kpis);
        $this->assertArrayHasKey('unpaid_invoices', $kpis);
        $this->assertArrayHasKey('unbilled_time', $kpis);
        $this->assertArrayHasKey('vat_summary', $kpis);
        $this->assertArrayHasKey('alerts', $kpis);
    }

    public function test_get_available_years(): void
    {
        $client = Client::factory()->create();

        Invoice::factory()->create([
            'client_id' => $client->id,
            'issued_at' => now()->setYear(2024),
        ]);

        Invoice::factory()->create([
            'client_id' => $client->id,
            'issued_at' => now()->setYear(2025),
        ]);

        Expense::factory()->create([
            'date' => now()->setYear(2023),
        ]);

        $years = $this->service->getAvailableYears();

        $this->assertContains(2023, $years);
        $this->assertContains(2024, $years);
        $this->assertContains(2025, $years);
        // Years should be in descending order
        $this->assertEquals(2025, $years[0]);
    }

    public function test_get_available_years_returns_current_year_if_empty(): void
    {
        $years = $this->service->getAvailableYears();

        $this->assertContains(now()->year, $years);
    }

    public function test_calculate_percentage_change_with_zero_previous(): void
    {
        $client = Client::factory()->create();

        // Only current year revenue
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => 1000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $kpis = $this->service->getKpis(now()->year);

        // When previous is 0 and current > 0, should be 100%
        $this->assertEquals(100.0, $kpis['annual_revenue_change']);
    }
}
