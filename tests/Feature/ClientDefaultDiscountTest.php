<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Remise permanente par client (FEAT-108).
 *
 * La règle qui porte cette fonctionnalité tient en une phrase : **la remise est
 * recopiée, jamais reliée**. Elle est écrite sur le document au moment de sa
 * création et n'en bouge plus. C'est ce que vérifient en priorité les tests
 * ci-dessous : une remise relue à l'affichage réécrirait l'historique
 * comptable d'un compte à chaque renégociation commerciale.
 */
class ClientDefaultDiscountTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($this->user);
        BusinessSettings::factory()->create(['user_id' => $this->user->id]);
    }

    private function client(array $attributes = []): Client
    {
        return Client::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'default_discount_type' => 'percent',
            'default_discount_value' => 10,
        ], $attributes));
    }

    /** @return array<string, mixed> */
    private function invoicePayload(Client $client, array $extra = []): array
    {
        return array_merge([
            'client_id' => $client->id,
            'issued_at' => '2026-08-06',
            'due_at' => '2026-09-05',
            'items' => [
                ['title' => 'Prestation', 'quantity' => 1, 'unit_price' => 1000, 'vat_rate' => 17],
            ],
        ], $extra);
    }

    private function createInvoice(Client $client, array $extra = []): Invoice
    {
        $this->post(route('invoices.store'), $this->invoicePayload($client, $extra))
            ->assertSessionHasNoErrors();

        return Invoice::where('client_id', $client->id)->latest('id')->firstOrFail();
    }

    // --- Recopie à la création --------------------------------------------------

    public function test_la_remise_du_client_est_reprise_sur_la_facture(): void
    {
        $invoice = $this->createInvoice($this->client());

        $this->assertCount(1, $invoice->discounts);
        $this->assertSame('percent', $invoice->discounts[0]->type);
        $this->assertEquals(10.0, $invoice->discounts[0]->value);
    }

    public function test_la_remise_reduit_reellement_le_total(): void
    {
        $invoice = $this->createInvoice($this->client())->fresh();

        // 1 000 HT − 10 % = 900 ; TVA 17 % sur 900 = 153.
        $this->assertEquals(900.0, (float) $invoice->total_ht);
        $this->assertEquals(153.0, (float) $invoice->total_vat);
        $this->assertEquals(1053.0, (float) $invoice->total_ttc);
    }

    public function test_un_client_sans_remise_ne_produit_aucune_ligne(): void
    {
        $invoice = $this->createInvoice($this->client(['default_discount_value' => 0]));

        $this->assertCount(0, $invoice->discounts);
    }

    public function test_une_remise_en_montant_fixe_est_reprise_telle_quelle(): void
    {
        $client = $this->client(['default_discount_type' => 'amount', 'default_discount_value' => 150]);

        $invoice = $this->createInvoice($client)->fresh();

        $this->assertSame('amount', $invoice->discounts[0]->type);
        $this->assertEquals(850.0, (float) $invoice->total_ht);
    }

    // --- Le point central : aucune relecture ------------------------------------

    public function test_modifier_la_remise_du_client_ne_touche_pas_une_facture_existante(): void
    {
        $client = $this->client();
        $brouillon = $this->createInvoice($client);
        $finalisee = $this->createInvoice($client);
        $finalisee->update(['status' => Invoice::STATUS_FINALIZED]);

        $client->update(['default_discount_value' => 40]);

        foreach ([$brouillon, $finalisee] as $invoice) {
            $invoice->refresh();
            $this->assertEquals(10.0, $invoice->discounts[0]->value, 'La remise recopiée doit être figée.');
            $this->assertEquals(900.0, (float) $invoice->total_ht);
        }
    }

    public function test_supprimer_la_remise_du_client_ne_vide_pas_les_factures_passees(): void
    {
        $client = $this->client();
        $invoice = $this->createInvoice($client);

        $client->update(['default_discount_value' => 0]);

        $this->assertCount(1, $invoice->fresh()->discounts);
    }

    // --- Ce que la requête demande prime toujours -------------------------------

    public function test_une_remise_transmise_prime_sur_le_defaut_du_client(): void
    {
        $invoice = $this->createInvoice($this->client(), [
            'discounts' => [['label' => 'Geste commercial', 'type' => 'percent', 'value' => 25]],
        ]);

        $this->assertCount(1, $invoice->discounts);
        $this->assertEquals(25.0, $invoice->discounts[0]->value);
        $this->assertSame('Geste commercial', $invoice->discounts[0]->label);
    }

    public function test_un_tableau_de_remises_vide_signifie_aucune_remise(): void
    {
        // Distinction volontaire : clé absente = « rien de précisé, applique le
        // défaut » ; tableau vide = « pas de remise sur ce document ».
        $invoice = $this->createInvoice($this->client(), ['discounts' => []]);

        $this->assertCount(0, $invoice->discounts);
    }

    // --- Libellé ----------------------------------------------------------------

    public function test_le_libelle_choisi_par_l_utilisateur_est_repris(): void
    {
        $client = $this->client(['default_discount_label' => 'Accord-cadre 2026']);

        $this->assertSame('Accord-cadre 2026', $this->createInvoice($client)->discounts[0]->label);
    }

    public function test_sans_libelle_la_remise_en_recoit_un_lisible(): void
    {
        $label = $this->createInvoice($this->client())->discounts[0]->label;

        $this->assertNotEmpty($label);
        $this->assertNotSame('percent', $label);
    }

    // --- Devis ------------------------------------------------------------------

    public function test_le_devis_recoit_la_meme_remise_que_la_facture(): void
    {
        $client = $this->client();

        // Sans cela un devis remisé donnerait une facture au prix fort, et
        // l'écart se découvrirait devant le client.
        $this->post(route('quotes.store'), [
            'client_id' => $client->id,
            'issued_at' => '2026-08-06',
            'valid_until' => '2026-09-06',
            'items' => [['title' => 'Prestation', 'quantity' => 1, 'unit_price' => 1000, 'vat_rate' => 17]],
        ])->assertSessionHasNoErrors();

        $quote = Quote::where('client_id', $client->id)->latest('id')->firstOrFail();

        $this->assertCount(1, $quote->discounts);
        $this->assertEquals(10.0, $quote->discounts[0]->value);
    }

    public function test_convertir_un_devis_remise_donne_une_facture_remisee_une_seule_fois(): void
    {
        $client = $this->client();

        $this->post(route('quotes.store'), [
            'client_id' => $client->id,
            'issued_at' => '2026-08-06',
            'valid_until' => '2026-09-06',
            'items' => [['title' => 'Prestation', 'quantity' => 1, 'unit_price' => 1000, 'vat_rate' => 17]],
        ])->assertSessionHasNoErrors();

        $quote = Quote::where('client_id', $client->id)->latest('id')->firstOrFail();
        $quote->update(['status' => Quote::STATUS_ACCEPTED]);

        $invoice = app(\App\Actions\ConvertQuoteToInvoiceAction::class)->execute($quote->fresh());

        // La remise vient du devis, pas d'une seconde lecture du client : elle
        // ne doit apparaître qu'une fois, et le total ne pas être remisé deux fois.
        $this->assertCount(1, $invoice->fresh()->discounts);
        $this->assertEquals(900.0, (float) $invoice->fresh()->total_ht);
    }

    // --- Validation et cloisonnement --------------------------------------------

    public function test_un_pourcentage_superieur_a_cent_est_refuse(): void
    {
        $this->putJson(route('clients.update', $this->client()), [
            'name' => 'Client',
            'default_discount_type' => 'percent',
            'default_discount_value' => 120,
        ])->assertStatus(422);
    }

    public function test_un_montant_fixe_superieur_a_cent_reste_accepte(): void
    {
        // Le plafond de 100 ne vaut que pour un pourcentage : 250 € de remise
        // sur une facture de 3 000 € est parfaitement ordinaire.
        $this->putJson(route('clients.update', $this->client()), [
            'name' => 'Client',
            'default_discount_type' => 'amount',
            'default_discount_value' => 250,
        ])->assertSessionHasNoErrors();
    }

    public function test_la_remise_d_un_client_d_un_autre_compte_reste_hors_de_portee(): void
    {
        $autre = User::factory()->create();
        $client = Client::factory()->create([
            'user_id' => $autre->id,
            'default_discount_value' => 30,
        ]);

        // Le cloisonnement joue dès la validation : le client d'autrui n'est
        // pas une option sélectionnable, la facture n'existe donc jamais.
        $this->post(route('invoices.store'), $this->invoicePayload($client))
            ->assertSessionHasErrors('client_id');

        $this->assertSame(0, Invoice::withoutGlobalScopes()->where('client_id', $client->id)->count());
    }
}
