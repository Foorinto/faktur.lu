<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\User;
use App\Services\VatCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FEAT-100: le formulaire s'appuie sur determineScenario pour adapter la TVA.
 * On verrouille ici le contrat (taux + mention) dont dépend l'auto-adaptation.
 */
class VatScenarioTest extends TestCase
{
    use RefreshDatabase;

    private function scenarioFor(string $country, string $type, ?string $vat): array
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        BusinessSettings::factory()->create(['country_code' => 'LU', 'vat_regime' => 'assujetti']);
        $client = Client::factory()->create([
            'user_id' => $user->id,
            'country_code' => $country,
            'type' => $type,
            'vat_number' => $vat,
        ]);

        return app(VatCalculationService::class)->determineScenario($client);
    }

    public function test_intra_eu_b2b_is_reverse_charge_at_zero(): void
    {
        $s = $this->scenarioFor('FR', 'b2b', 'FR12345678901');
        $this->assertSame('reverse_charge', $s['mention']);
        $this->assertSame(0, $s['rate']);
    }

    public function test_non_eu_client_is_export_at_zero(): void
    {
        $s = $this->scenarioFor('US', 'b2b', null);
        $this->assertSame('export', $s['mention']);
        $this->assertSame(0, $s['rate']);
    }

    public function test_domestic_client_has_no_special_mention(): void
    {
        $s = $this->scenarioFor('LU', 'b2b', 'LU12345678');
        $this->assertNull($s['mention']);
        $this->assertGreaterThan(0, $s['rate']);
    }
}
