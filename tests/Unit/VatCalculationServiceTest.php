<?php

namespace Tests\Unit;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Services\VatCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private VatCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VatCalculationService();
    }

    public function test_determines_franchise_scenario_when_seller_is_vat_exempt(): void
    {
        BusinessSettings::factory()->franchise()->create();

        $client = Client::factory()->create([
            'type' => 'b2b',
            'country_code' => 'FR',
            'vat_number' => 'FR12345678901',
        ]);

        $scenario = $this->service->determineScenario($client);

        $this->assertEquals('FRANCHISE', $scenario['key']);
        $this->assertEquals(0, $scenario['rate']);
        $this->assertEquals('franchise', $scenario['mention']);
    }

    public function test_determines_b2b_intra_eu_scenario_for_eu_client_with_vat_number(): void
    {
        BusinessSettings::factory()->assujetti()->create();

        $client = Client::factory()->create([
            'type' => 'b2b',
            'country_code' => 'FR',
            'vat_number' => 'FR12345678901',
        ]);

        $scenario = $this->service->determineScenario($client);

        $this->assertEquals('B2B_INTRA_EU', $scenario['key']);
        $this->assertEquals(0, $scenario['rate']);
        $this->assertEquals('reverse_charge', $scenario['mention']);
    }

    public function test_determines_b2b_lu_scenario_for_luxembourg_b2b_client(): void
    {
        BusinessSettings::factory()->assujetti()->create();

        $client = Client::factory()->create([
            'type' => 'b2b',
            'country_code' => 'LU',
            'vat_number' => 'LU12345678',
        ]);

        $scenario = $this->service->determineScenario($client);

        $this->assertEquals('B2B_LU', $scenario['key']);
        $this->assertEquals(17, $scenario['rate']);
        $this->assertNull($scenario['mention']);
    }

    public function test_determines_b2c_lu_scenario_for_luxembourg_b2c_client(): void
    {
        BusinessSettings::factory()->assujetti()->create();

        $client = Client::factory()->create([
            'type' => 'b2c',
            'country_code' => 'LU',
        ]);

        $scenario = $this->service->determineScenario($client);

        $this->assertEquals('B2C_LU', $scenario['key']);
        $this->assertEquals(17, $scenario['rate']);
        $this->assertNull($scenario['mention']);
    }

    public function test_determines_export_scenario_for_non_eu_client(): void
    {
        BusinessSettings::factory()->assujetti()->create();

        $client = Client::factory()->create([
            'type' => 'b2b',
            'country_code' => 'US',
        ]);

        $scenario = $this->service->determineScenario($client);

        $this->assertEquals('EXPORT', $scenario['key']);
        $this->assertEquals(0, $scenario['rate']);
        $this->assertEquals('export', $scenario['mention']);
    }

    public function test_eu_b2b_without_vat_number_gets_luxembourg_vat(): void
    {
        BusinessSettings::factory()->assujetti()->create();

        $client = Client::factory()->create([
            'type' => 'b2b',
            'country_code' => 'DE',
            'vat_number' => null,
        ]);

        $scenario = $this->service->determineScenario($client);

        // B2B EU client without VAT number should get Luxembourg VAT
        $this->assertEquals('B2B_LU', $scenario['key']);
        $this->assertEquals(17, $scenario['rate']);
    }

    public function test_is_eu_country(): void
    {
        $this->assertTrue($this->service->isEuCountry('FR'));
        $this->assertTrue($this->service->isEuCountry('DE'));
        $this->assertTrue($this->service->isEuCountry('LU'));
        $this->assertTrue($this->service->isEuCountry('be')); // Case insensitive

        $this->assertFalse($this->service->isEuCountry('US'));
        $this->assertFalse($this->service->isEuCountry('GB'));
        $this->assertFalse($this->service->isEuCountry('CH'));
    }

    public function test_is_intra_eu_country(): void
    {
        $this->assertTrue($this->service->isIntraEuCountry('FR'));
        $this->assertTrue($this->service->isIntraEuCountry('DE'));

        $this->assertFalse($this->service->isIntraEuCountry('LU')); // Luxembourg is not intra-EU
        $this->assertFalse($this->service->isIntraEuCountry('US'));
    }

    public function test_validate_vat_number_format(): void
    {
        $this->assertTrue($this->service->validateVatNumber('FR12345678901'));
        $this->assertTrue($this->service->validateVatNumber('DE123456789'));
        $this->assertTrue($this->service->validateVatNumber('LU12345678'));
        $this->assertTrue($this->service->validateVatNumber('')); // Empty is valid (optional)

        $this->assertFalse($this->service->validateVatNumber('12345')); // No country prefix
        $this->assertFalse($this->service->validateVatNumber('XX')); // Too short
    }

    public function test_validate_vat_number_with_country_code(): void
    {
        $this->assertTrue($this->service->validateVatNumber('FR12345678901', 'FR'));
        $this->assertFalse($this->service->validateVatNumber('DE123456789', 'FR')); // Mismatch
    }

    public function test_get_all_scenarios(): void
    {
        $scenarios = VatCalculationService::getAllScenarios();

        $this->assertIsArray($scenarios);
        $this->assertCount(5, $scenarios);

        $keys = array_column($scenarios, 'key');
        $this->assertContains('B2B_INTRA_EU', $keys);
        $this->assertContains('B2B_LU', $keys);
        $this->assertContains('B2C_LU', $keys);
        $this->assertContains('FRANCHISE', $keys);
        $this->assertContains('EXPORT', $keys);
    }
}
