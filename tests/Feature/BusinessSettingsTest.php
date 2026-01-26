<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_settings_page_is_displayed(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->get(route('settings.business.edit'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Business')
            ->has('vatRegimes')
        );
    }

    public function test_settings_page_displays_existing_settings(): void
    {
        $settings = BusinessSettings::factory()->create([
            'company_name' => 'Test Company',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->get(route('settings.business.edit'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Business')
            ->where('settings.company_name', 'Test Company')
        );
    }

    public function test_settings_can_be_created(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->put(route('settings.business.update'), [
                'company_name' => 'New Company',
                'legal_name' => 'Legal Name',
                'address' => '123 Street',
                'postal_code' => 'L-1234',
                'city' => 'Luxembourg',
                'country_code' => 'LU',
                'matricule' => '12345678901',
                'iban' => 'LU123456789012345678',
                'bic' => 'BGLLLULL',
                'vat_regime' => 'franchise',
                'email' => 'test@example.lu',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('business_settings', [
            'company_name' => 'New Company',
        ]);
    }

    public function test_settings_can_be_updated(): void
    {
        BusinessSettings::factory()->create([
            'company_name' => 'Old Company',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->put(route('settings.business.update'), [
                'company_name' => 'Updated Company',
                'legal_name' => 'Legal Name',
                'address' => '123 Street',
                'postal_code' => 'L-1234',
                'city' => 'Luxembourg',
                'country_code' => 'LU',
                'matricule' => '12345678901',
                'iban' => 'LU123456789012345678',
                'bic' => 'BGLLLULL',
                'vat_regime' => 'franchise',
                'email' => 'test@example.lu',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('business_settings', [
            'company_name' => 'Updated Company',
        ]);
        $this->assertDatabaseCount('business_settings', 1);
    }

    public function test_matricule_validation_requires_11_to_13_digits(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->put(route('settings.business.update'), [
                'company_name' => 'Company',
                'legal_name' => 'Legal Name',
                'address' => '123 Street',
                'postal_code' => 'L-1234',
                'city' => 'Luxembourg',
                'country_code' => 'LU',
                'matricule' => '123456', // Invalid: less than 11 digits
                'iban' => 'LU123456789012345678',
                'bic' => 'BGLLLULL',
                'vat_regime' => 'franchise',
                'email' => 'test@example.lu',
            ]);

        $response->assertSessionHasErrors('matricule');
    }

    public function test_vat_number_validation_requires_valid_eu_format(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->put(route('settings.business.update'), [
                'company_name' => 'Company',
                'legal_name' => 'Legal Name',
                'address' => '123 Street',
                'postal_code' => 'L-1234',
                'city' => 'Luxembourg',
                'country_code' => 'LU',
                'matricule' => '12345678901',
                'vat_number' => '123', // Invalid: must start with 2 letter country code
                'iban' => 'LU123456789012345678',
                'bic' => 'BGLLLULL',
                'vat_regime' => 'assujetti',
                'email' => 'test@example.lu',
            ]);

        $response->assertSessionHasErrors('vat_number');
    }

    public function test_vat_number_required_when_assujetti(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->put(route('settings.business.update'), [
                'company_name' => 'Company',
                'legal_name' => 'Legal Name',
                'address' => '123 Street',
                'postal_code' => 'L-1234',
                'city' => 'Luxembourg',
                'country_code' => 'LU',
                'matricule' => '12345678901',
                'vat_number' => null, // Missing but required for assujetti
                'iban' => 'LU123456789012345678',
                'bic' => 'BGLLLULL',
                'vat_regime' => 'assujetti',
                'email' => 'test@example.lu',
            ]);

        $response->assertSessionHasErrors('vat_number');
    }

    public function test_vat_number_optional_when_franchise(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->put(route('settings.business.update'), [
                'company_name' => 'Company',
                'legal_name' => 'Legal Name',
                'address' => '123 Street',
                'postal_code' => 'L-1234',
                'city' => 'Luxembourg',
                'country_code' => 'LU',
                'matricule' => '12345678901',
                'vat_number' => null, // Optional for franchise
                'iban' => 'LU123456789012345678',
                'bic' => 'BGLLLULL',
                'vat_regime' => 'franchise',
                'email' => 'test@example.lu',
            ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_guest_cannot_access_settings(): void
    {
        $response = $this->get(route('settings.business.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_singleton_pattern_only_one_record_exists(): void
    {
        BusinessSettings::factory()->create();

        $this
            ->actingAs($this->user)
            ->put(route('settings.business.update'), [
                'company_name' => 'First Update',
                'legal_name' => 'Legal Name',
                'address' => '123 Street',
                'postal_code' => 'L-1234',
                'city' => 'Luxembourg',
                'country_code' => 'LU',
                'matricule' => '12345678901',
                'iban' => 'LU123456789012345678',
                'bic' => 'BGLLLULL',
                'vat_regime' => 'franchise',
                'email' => 'test@example.lu',
            ]);

        $this
            ->actingAs($this->user)
            ->put(route('settings.business.update'), [
                'company_name' => 'Second Update',
                'legal_name' => 'Legal Name',
                'address' => '123 Street',
                'postal_code' => 'L-1234',
                'city' => 'Luxembourg',
                'country_code' => 'LU',
                'matricule' => '12345678901',
                'iban' => 'LU123456789012345678',
                'bic' => 'BGLLLULL',
                'vat_regime' => 'franchise',
                'email' => 'test@example.lu',
            ]);

        $this->assertDatabaseCount('business_settings', 1);
        $this->assertDatabaseHas('business_settings', [
            'company_name' => 'Second Update',
        ]);
    }
}
