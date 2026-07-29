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

    public function test_franchise_seller_cannot_persist_a_non_zero_rate(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        BusinessSettings::factory()->create(['vat_regime' => 'franchise']);

        // Envoi hors interface (API, formulaire périmé) : le serveur doit refuser.
        $this->post(route('products.store', ['locale' => 'fr']), [
            'designation' => 'Micro d\'occasion',
            'unit_price_ht' => 120,
            'vat_rate' => 17,
        ])->assertSessionHasErrors('vat_rate');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_franchise_seller_can_persist_a_zero_rate(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        BusinessSettings::factory()->create(['vat_regime' => 'franchise']);

        $this->post(route('products.store', ['locale' => 'fr']), [
            'designation' => 'Micro d\'occasion',
            'unit_price_ht' => 120,
            'vat_rate' => 0,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', ['designation' => 'Micro d\'occasion', 'vat_rate' => 0]);
    }

    public function test_vat_registered_seller_can_still_persist_a_standard_rate(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        BusinessSettings::factory()->create(['vat_regime' => 'assujetti']);

        $this->post(route('products.store', ['locale' => 'fr']), [
            'designation' => 'Prestation',
            'unit_price_ht' => 100,
            'vat_rate' => 17,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', ['designation' => 'Prestation', 'vat_rate' => 17]);
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

    public function test_franchise_seller_can_still_edit_a_legacy_product_kept_at_17(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        BusinessSettings::factory()->create(['vat_regime' => 'franchise']);

        // Produit créé AVANT le passage en franchise : il porte encore 17 %.
        $product = \App\Models\Product::create([
            'user_id' => $user->id,
            'designation' => 'Ancien article',
            'unit_price_ht' => 100,
            'vat_rate' => 17,
            'unit' => 'piece',
        ]);

        // Renommer ne doit pas être bloqué sous prétexte du taux hérité.
        $this->put(route('products.update', ['locale' => 'fr', 'product' => $product->id]), [
            'designation' => 'Article renommé',
            'unit_price_ht' => 100,
            'vat_rate' => 17,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'designation' => 'Article renommé']);
    }
}
