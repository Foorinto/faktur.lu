<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pays d'établissement : le Luxembourg, et lui seul.
 *
 * Une entreprise belge pouvait s'inscrire et découvrir qu'aucun export TVA
 * n'existait pour son administration — `listing_tva` et `gdpdu` étaient
 * déclarés dans la configuration et implémentés nulle part. La promesse est
 * retirée plutôt que laissée à découvrir.
 *
 * Ce que ces tests protègent surtout, c'est la frontière : facturer, acheter et
 * autoliquider à l'étranger restent le quotidien d'une entreprise
 * luxembourgeoise, et ne doivent rien perdre à cette fermeture.
 */
class EstablishmentCountryTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_luxembourg_is_offered_as_a_place_of_business(): void
    {
        $pays = BusinessSettings::getSupportedCountries();

        $this->assertCount(1, $pays);
        $this->assertSame('LU', $pays[0]['value']);
    }

    /**
     * Le formulaire n'est pas la seule porte : un POST direct doit être refusé
     * de la même façon.
     */
    public function test_a_direct_post_cannot_declare_a_foreign_company(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);
        BusinessSettings::factory()->create();

        $this->put(route('settings.business.update'), [
            'company_name' => 'Société Test',
            'legal_name' => 'Société Test SARL',
            'address' => '1 rue de Paris',
            'postal_code' => '75001',
            'city' => 'Paris',
            'country_code' => 'FR',
            'matricule' => '123456789',
            'vat_regime' => 'franchise',
        ])->assertSessionHasErrors('country_code');
    }

    // --- La frontière : ce qui doit continuer de fonctionner ---------------

    /**
     * Les grilles de TVA des quatre pays servent aux ACHATS. Les retirer
     * casserait la saisie d'une facture Amazon.de — précisément ce pour quoi
     * elles ont été mises en place.
     */
    public function test_foreign_vat_grids_are_untouched_for_purchases(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('expenses.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('vatRatesByCountry.DE')
                ->has('vatRatesByCountry.FR')
                ->has('vatRatesByCountry.BE')
                ->has('vatRatesByCountry.LU')
            );
    }

    public function test_the_supplier_country_list_still_spans_the_union(): void
    {
        $pays = array_column(\App\Models\Expense::getSupplierCountries(), 'code');

        $this->assertContains('DE', $pays);
        $this->assertContains('FR', $pays);
        $this->assertGreaterThan(20, count($pays), 'Les Vingt-Sept doivent rester proposés à l\'achat.');
    }

    /**
     * Un client étranger reste un client : c'est lui qui déclenche
     * l'autoliquidation et les mentions légales correspondantes.
     */
    public function test_a_foreign_client_can_still_be_created(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $this->post(route('clients.store'), [
            'name' => 'Kunde GmbH',
            'email' => 'kunde@example.de',
            'country_code' => 'DE',
            'type' => 'b2b',
            'currency' => 'EUR',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clients', ['name' => 'Kunde GmbH', 'country_code' => 'DE']);
    }

    /**
     * La configuration des quatre pays reste en place. Rouvrir le choix
     * consisterait à rétablir la liste, sans rien reconstruire.
     */
    public function test_the_country_configuration_is_preserved(): void
    {
        foreach (['LU', 'FR', 'BE', 'DE'] as $code) {
            $this->assertNotEmpty(
                config("countries.{$code}.vat_rates"),
                "La grille de TVA de {$code} sert aux achats et doit rester."
            );
        }
    }
}
