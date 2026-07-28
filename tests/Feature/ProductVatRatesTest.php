<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les taux proposés dans le catalogue suivent le régime de TVA de l'entreprise :
 * en franchise, seul le 0 % est applicable (aucun 17 % pré-rempli).
 */
class ProductVatRatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_franchise_seller_is_only_offered_zero_rate(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        BusinessSettings::factory()->create(['vat_regime' => 'franchise']);

        $this->get(route('products.create', ['locale' => 'fr']))
            ->assertInertia(fn ($page) => $page
                ->component('Products/Create')
                ->where('vatRates', [0])
            );
    }

    public function test_vat_registered_seller_is_offered_country_rates(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        BusinessSettings::factory()->create(['vat_regime' => 'assujetti']);

        $this->get(route('products.create', ['locale' => 'fr']))
            ->assertInertia(function ($page) {
                $rates = $page->toArray()['props']['vatRates'];
                $this->assertContains(17, $rates);
                $this->assertContains(0, $rates);
            });
    }
}
