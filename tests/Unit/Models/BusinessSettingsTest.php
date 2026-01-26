<?php

namespace Tests\Unit\Models;

use App\Models\BusinessSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_snapshot_contains_all_required_fields(): void
    {
        $settings = BusinessSettings::factory()->create([
            'company_name' => 'Test Company',
            'legal_name' => 'Test Legal Name',
            'address' => '123 Test Street',
            'postal_code' => 'L-1234',
            'city' => 'Luxembourg',
            'country_code' => 'LU',
            'vat_number' => 'LU12345678',
            'matricule' => '12345678901',
            'iban' => 'LU123456789012345678',
            'bic' => 'BGLLLULL',
            'vat_regime' => 'assujetti',
            'phone' => '+352 123 456',
            'email' => 'test@example.lu',
        ]);

        $snapshot = $settings->toSnapshot();

        $this->assertEquals('Test Company', $snapshot['company_name']);
        $this->assertEquals('Test Legal Name', $snapshot['legal_name']);
        $this->assertEquals('123 Test Street', $snapshot['address']);
        $this->assertEquals('L-1234', $snapshot['postal_code']);
        $this->assertEquals('Luxembourg', $snapshot['city']);
        $this->assertEquals('LU', $snapshot['country_code']);
        $this->assertEquals('LU12345678', $snapshot['vat_number']);
        $this->assertEquals('12345678901', $snapshot['matricule']);
        $this->assertEquals('LU123456789012345678', $snapshot['iban']);
        $this->assertEquals('BGLLLULL', $snapshot['bic']);
        $this->assertEquals('assujetti', $snapshot['vat_regime']);
        $this->assertEquals('+352 123 456', $snapshot['phone']);
        $this->assertEquals('test@example.lu', $snapshot['email']);
    }

    public function test_to_snapshot_contains_logo_path(): void
    {
        $settings = BusinessSettings::factory()->create([
            'logo_path' => 'logos/test.png',
        ]);

        $snapshot = $settings->toSnapshot();

        $this->assertArrayHasKey('logo_path', $snapshot);
        $this->assertEquals('logos/test.png', $snapshot['logo_path']);
    }

    public function test_is_vat_exempt_returns_true_for_franchise(): void
    {
        $settings = BusinessSettings::factory()->franchise()->create();

        $this->assertTrue($settings->isVatExempt());
        $this->assertFalse($settings->isVatRegistered());
    }

    public function test_is_vat_registered_returns_true_for_assujetti(): void
    {
        $settings = BusinessSettings::factory()->assujetti()->create();

        $this->assertTrue($settings->isVatRegistered());
        $this->assertFalse($settings->isVatExempt());
    }

    public function test_get_instance_returns_first_record(): void
    {
        $settings = BusinessSettings::factory()->create();

        $instance = BusinessSettings::getInstance();

        $this->assertNotNull($instance);
        $this->assertEquals($settings->id, $instance->id);
    }

    public function test_get_instance_returns_null_when_no_records(): void
    {
        $instance = BusinessSettings::getInstance();

        $this->assertNull($instance);
    }

    public function test_is_configured_returns_true_when_settings_exist(): void
    {
        BusinessSettings::factory()->create();

        $this->assertTrue(BusinessSettings::isConfigured());
    }

    public function test_is_configured_returns_false_when_no_settings(): void
    {
        $this->assertFalse(BusinessSettings::isConfigured());
    }

    public function test_formatted_address_attribute(): void
    {
        $settings = BusinessSettings::factory()->create([
            'address' => '12 Rue de la Gare',
            'postal_code' => 'L-1234',
            'city' => 'Luxembourg',
            'country_code' => 'LU',
        ]);

        $expected = "12 Rue de la Gare\nL-1234 Luxembourg";
        $this->assertEquals($expected, $settings->formatted_address);
    }

    public function test_formatted_address_includes_country_when_not_luxembourg(): void
    {
        $settings = BusinessSettings::factory()->create([
            'address' => '12 Rue de Paris',
            'postal_code' => '75001',
            'city' => 'Paris',
            'country_code' => 'FR',
        ]);

        $expected = "12 Rue de Paris\n75001 Paris\nFR";
        $this->assertEquals($expected, $settings->formatted_address);
    }
}
