<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le schéma Peppol luxembourgeois, et le piège des clés numériques.
 *
 * PHP convertit en ENTIER toute clé de tableau qui ressemble à un nombre.
 * Dans `PEPPOL_SCHEMES`, « 9938 » y passait ; « 0009 », « 0208 » et tous les
 * autres non, leur zéro initial les protégeant. Le seul schéma luxembourgeois
 * partait donc vers l'interface sous forme de nombre, revenait tel quel, et la
 * règle `string` le refusait — « peppol endpoint scheme must be a string ».
 *
 * Aucun utilisateur luxembourgeois ne pouvait enregistrer son identifiant
 * Peppol, sur la seule fonctionnalité qui les concerne tous.
 */
class PeppolSchemeLuxembourgTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * La cause, prise à sa source : ce que l'interface reçoit doit être une
     * chaîne pour TOUS les schémas, sans exception luxembourgeoise.
     */
    public function test_every_scheme_reaches_the_interface_as_a_string(): void
    {
        foreach (BusinessSettings::getPeppolSchemeOptions() as $option) {
            $this->assertIsString(
                $option['value'],
                "Le schéma {$option['label']} part en ".gettype($option['value']).'.'
            );
        }
    }

    /**
     * Le symptôme : un client enregistré avec la valeur telle que la liste
     * déroulante la porte. C'est le parcours exact de l'utilisateur.
     */
    public function test_a_client_accepts_the_luxembourg_scheme(): void
    {
        $schema = collect(BusinessSettings::getPeppolSchemeOptions())
            ->firstWhere('value', '9938')['value'];

        $this->post(route('clients.store'), [
            'name' => 'Client Peppol',
            'email' => 'peppol@example.lu',
            'address' => '1 rue du Test',
            'postal_code' => 'L-1234',
            'city' => 'Luxembourg',
            'country_code' => 'LU',
            'type' => 'b2b',
            'currency' => 'EUR',
            'vat_number' => 'LU12345678',
            'peppol_endpoint_scheme' => $schema,
            'peppol_endpoint_id' => 'LU12345678',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'name' => 'Client Peppol',
            'peppol_endpoint_scheme' => '9938',
            'peppol_endpoint_id' => 'LU12345678',
        ]);
    }

    /**
     * Le filet : un client d'API qui envoie 9938 sans guillemets a raison de le
     * faire — ce sont quatre chiffres. Le refuser pour sa forme serait aussi
     * arbitraire que le défaut qu'on vient de corriger.
     */
    public function test_the_scheme_is_accepted_as_a_json_number(): void
    {
        $this->postJson(route('clients.store'), [
            'name' => 'Client API',
            'email' => 'api@example.lu',
            'address' => '1 rue du Test',
            'postal_code' => 'L-1234',
            'city' => 'Luxembourg',
            'country_code' => 'LU',
            'type' => 'b2b',
            'currency' => 'EUR',
            'peppol_endpoint_scheme' => 9938,
            'peppol_endpoint_id' => 'LU12345678',
        ])->assertValid('peppol_endpoint_scheme');

        $this->assertDatabaseHas('clients', [
            'name' => 'Client API',
            'peppol_endpoint_scheme' => '9938',
        ]);
    }

    /**
     * Et les paramètres de l'entreprise, l'autre bout du même problème : sans
     * identifiant côté vendeur, aucune facture ne part.
     */
    public function test_business_settings_accept_the_luxembourg_scheme(): void
    {
        $settings = BusinessSettings::factory()->create();

        $this->put(route('settings.business.update'), array_merge(
            $settings->only([
                'company_name', 'legal_name', 'address', 'postal_code', 'city',
                'country_code', 'vat_number', 'matricule', 'iban', 'bic',
                'vat_regime', 'email',
            ]),
            [
                'peppol_endpoint_scheme' => 9938,
                'peppol_endpoint_id' => 'LU12345678',
            ]
        ))->assertSessionHasNoErrors();

        $this->assertSame('9938', $settings->fresh()->peppol_endpoint_scheme);
    }
}
