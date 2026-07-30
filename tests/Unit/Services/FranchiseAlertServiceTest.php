<?php

namespace Tests\Unit\Services;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\FranchiseAlertService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FranchiseAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seuil de franchise réel du pays, lu depuis la configuration.
     *
     * Ces tests codaient le seuil en dur (35 000 €) : ils ont donc tous cassé
     * quand le Luxembourg l'a relevé à 50 000 €, alors que l'application était
     * correcte. On dérive désormais du même référentiel que le code testé.
     */
    private function threshold(string $country = 'LU'): float
    {
        return (float) config("countries.{$country}.franchise.threshold");
    }

    /** Un chiffre d'affaires représentant X % du seuil. */
    private function revenueAt(float $percent, string $country = 'LU'): float
    {
        return round($this->threshold($country) * $percent / 100, 2);
    }

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_warning_threshold_percentage_constant(): void
    {
        $this->assertEquals(90, FranchiseAlertService::WARNING_THRESHOLD_PERCENTAGE);
    }

    public function test_tax_authorities_contains_all_supported_countries(): void
    {
        $authorities = FranchiseAlertService::TAX_AUTHORITIES;

        $this->assertArrayHasKey('LU', $authorities);
        $this->assertArrayHasKey('FR', $authorities);
        $this->assertArrayHasKey('BE', $authorities);
        $this->assertArrayHasKey('DE', $authorities);

        // Check structure
        foreach ($authorities as $country => $info) {
            $this->assertArrayHasKey('name', $info);
            $this->assertArrayHasKey('full_name', $info);
            $this->assertArrayHasKey('url', $info);
        }
    }

    public function test_get_yearly_revenue_returns_zero_without_settings(): void
    {
        $service = new FranchiseAlertService();

        $this->assertEquals(0.0, $service->getYearlyRevenue());
    }

    public function test_get_yearly_revenue_sums_last_12_months(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $client = Client::factory()->create();

        // Invoice from 6 months ago (should be counted)
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now()->subMonths(6),
            'total_ht' => 10000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        // Invoice from 3 months ago (should be counted)
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_PAID,
            'issued_at' => now()->subMonths(3),
            'total_ht' => 5000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        // Invoice from 14 months ago (should NOT be counted - outside 12 month window)
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now()->subMonths(14),
            'total_ht' => 20000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $service = new FranchiseAlertService();
        $revenue = $service->getYearlyRevenue();

        $this->assertEquals(15000.0, $revenue);
    }

    public function test_get_yearly_revenue_excludes_drafts(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $client = Client::factory()->create();

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => 10000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_DRAFT,
            'issued_at' => now(),
            'total_ht' => 5000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $service = new FranchiseAlertService();
        $revenue = $service->getYearlyRevenue();

        $this->assertEquals(10000.0, $revenue);
    }

    public function test_get_yearly_revenue_excludes_credit_notes(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $client = Client::factory()->create();

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => 10000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => 2000,
            'type' => Invoice::TYPE_CREDIT_NOTE,
        ]);

        $service = new FranchiseAlertService();
        $revenue = $service->getYearlyRevenue();

        $this->assertEquals(10000.0, $revenue);
    }

    public function test_get_threshold_returns_default_without_settings(): void
    {
        $service = new FranchiseAlertService();

        $this->assertEquals($this->threshold(), $service->getThreshold());
    }

    public function test_get_threshold_returns_country_specific_threshold(): void
    {
        // Luxembourg threshold
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $service = new FranchiseAlertService();
        $this->assertEquals($this->threshold(), $service->getThreshold());
    }

    public function test_get_threshold_returns_belgium_threshold(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'BE',
            'vat_regime' => 'franchise',
        ]);

        $service = new FranchiseAlertService();
        $this->assertEquals(25000, $service->getThreshold());
    }

    public function test_get_threshold_returns_germany_threshold(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'DE',
            'vat_regime' => 'franchise',
        ]);

        $service = new FranchiseAlertService();
        $this->assertEquals(22000, $service->getThreshold());
    }

    public function test_get_remaining_amount_calculates_correctly(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $client = Client::factory()->create();

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => 20000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $service = new FranchiseAlertService();

        $this->assertEquals($this->threshold() - $this->revenueAt(40), $service->getRemainingAmount());
    }

    public function test_get_remaining_amount_returns_zero_when_exceeded(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $client = Client::factory()->create();

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => $this->revenueAt(110), // au-delà du seuil
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $service = new FranchiseAlertService();

        $this->assertEquals(0.0, $service->getRemainingAmount());
    }

    public function test_get_percentage_used(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $client = Client::factory()->create();

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => $this->revenueAt(50),
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $service = new FranchiseAlertService();

        $this->assertEquals(50.0, $service->getPercentageUsed());
    }

    public function test_is_approaching_threshold_at_90_percent(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $client = Client::factory()->create();

        // 90 % du seuil
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => $this->revenueAt(90),
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $service = new FranchiseAlertService();

        $this->assertTrue($service->isApproachingThreshold());
        $this->assertFalse($service->isThresholdExceeded());
    }

    public function test_is_approaching_threshold_false_below_90_percent(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $client = Client::factory()->create();

        // 80 % du seuil
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => $this->revenueAt(80),
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $service = new FranchiseAlertService();

        $this->assertFalse($service->isApproachingThreshold());
    }

    public function test_is_approaching_threshold_false_when_exceeded(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $client = Client::factory()->create();

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => $this->revenueAt(110), // au-delà du seuil
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $service = new FranchiseAlertService();

        $this->assertFalse($service->isApproachingThreshold());
        $this->assertTrue($service->isThresholdExceeded());
    }

    public function test_is_threshold_exceeded(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $client = Client::factory()->create();

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => $this->revenueAt(110), // au-delà du seuil
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $service = new FranchiseAlertService();

        $this->assertTrue($service->isThresholdExceeded());
    }

    public function test_get_country_code_returns_lu_by_default(): void
    {
        $service = new FranchiseAlertService();

        $this->assertEquals('LU', $service->getCountryCode());
    }

    public function test_get_country_code_returns_user_country(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'FR',
            'vat_regime' => 'franchise',
        ]);

        $service = new FranchiseAlertService();

        $this->assertEquals('FR', $service->getCountryCode());
    }

    public function test_get_tax_authority_returns_correct_info(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'FR',
            'vat_regime' => 'franchise',
        ]);

        $service = new FranchiseAlertService();
        $authority = $service->getTaxAuthority();

        $this->assertEquals('SIE', $authority['name']);
        $this->assertStringContainsString('Service des', $authority['full_name']);
    }

    public function test_get_legal_reference(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $service = new FranchiseAlertService();

        $this->assertEquals('Art. 57 du Code de la TVA luxembourgeois', $service->getLegalReference());
    }

    public function test_is_in_franchise_regime_returns_true_for_franchise(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $service = new FranchiseAlertService();

        $this->assertTrue($service->isInFranchiseRegime());
    }

    public function test_is_in_franchise_regime_returns_false_for_assujetti(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'assujetti',
        ]);

        $service = new FranchiseAlertService();

        $this->assertFalse($service->isInFranchiseRegime());
    }

    public function test_get_alert_status_returns_null_for_assujetti(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'assujetti',
        ]);

        $client = Client::factory()->create();

        // Even with exceeded revenue, assujetti users should not get alerts
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => $this->revenueAt(110), // au-delà du seuil
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $service = new FranchiseAlertService();

        $this->assertNull($service->getAlertStatus());
    }

    public function test_get_alert_status_returns_exceeded_for_franchise(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $client = Client::factory()->create();

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => $this->revenueAt(110), // au-delà du seuil
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $service = new FranchiseAlertService();

        $this->assertEquals('exceeded', $service->getAlertStatus());
    }

    public function test_get_alert_status_returns_warning_for_approaching(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $client = Client::factory()->create();

        // 91 % du seuil
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => $this->revenueAt(91),
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $service = new FranchiseAlertService();

        $this->assertEquals('warning', $service->getAlertStatus());
    }

    public function test_get_alert_status_returns_null_below_warning_threshold(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $client = Client::factory()->create();

        // 57 % du seuil
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => 20000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $service = new FranchiseAlertService();

        $this->assertNull($service->getAlertStatus());
    }

    public function test_get_franchise_alert_data_returns_complete_structure(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $client = Client::factory()->create();

        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => $this->revenueAt(91),
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $service = new FranchiseAlertService();
        $data = $service->getFranchiseAlertData();

        $this->assertArrayHasKey('show', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('yearly_revenue', $data);
        $this->assertArrayHasKey('threshold', $data);
        $this->assertArrayHasKey('remaining_amount', $data);
        $this->assertArrayHasKey('percentage_used', $data);
        $this->assertArrayHasKey('country_code', $data);
        $this->assertArrayHasKey('tax_authority', $data);
        $this->assertArrayHasKey('legal_reference', $data);
        $this->assertArrayHasKey('is_franchise_regime', $data);

        $this->assertTrue($data['show']);
        $this->assertEquals('warning', $data['status']);
        $this->assertEquals($this->revenueAt(91), $data['yearly_revenue']);
        $this->assertEquals($this->threshold(), $data['threshold']);
        $this->assertEquals($this->threshold() - $this->revenueAt(91), $data['remaining_amount']);
        $this->assertEquals('LU', $data['country_code']);
        $this->assertTrue($data['is_franchise_regime']);
    }

    public function test_get_franchise_alert_data_returns_hidden_when_no_alert(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $client = Client::factory()->create();

        // Only 10000 - well below threshold
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => 10000,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $service = new FranchiseAlertService();
        $data = $service->getFranchiseAlertData();

        $this->assertFalse($data['show']);
        $this->assertNull($data['status']);
    }

    public function test_get_franchise_alert_data_without_settings(): void
    {
        $service = new FranchiseAlertService();
        $data = $service->getFranchiseAlertData();

        $this->assertFalse($data['show']);
        $this->assertNull($data['status']);
        $this->assertEquals(0, $data['yearly_revenue']);
        $this->assertEquals($this->threshold(), $data['threshold']);
        $this->assertEquals($this->threshold(), $data['remaining_amount']);
        $this->assertEquals('LU', $data['country_code']);
    }

    public function test_custom_warning_threshold_percentage(): void
    {
        BusinessSettings::factory()->create([
            'country_code' => 'LU',
            'vat_regime' => 'franchise',
        ]);

        $client = Client::factory()->create();

        // 80 % du seuil
        Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now(),
            'total_ht' => $this->revenueAt(80),
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $service = new FranchiseAlertService();

        // Default 90% - should not trigger
        $this->assertFalse($service->isApproachingThreshold());

        // Custom 80% - should trigger
        $this->assertTrue($service->isApproachingThreshold(80));
    }
}
