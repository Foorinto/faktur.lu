<?php

namespace Tests\Feature;

use App\Actions\CreateCreditNoteAction;
use App\Actions\FinalizeInvoiceAction;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gestion de stock (FEAT-116).
 *
 * Le stock suit l'émission, jamais le brouillon. Il est un journal : chaque
 * mouvement est une ligne, le stock courant en est la somme. Une note de crédit
 * réintègre. Et le stock d'un compte ne fuit jamais chez un autre.
 */
class StockTest extends TestCase
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

    private function trackedProduct(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'type' => Product::TYPE_PRODUCT,
            'track_stock' => true,
        ], $attrs));
    }

    private function draftWith(Product $product, float $quantity = 3): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_DRAFT,
            'issued_at' => '2026-03-10',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'title' => $product->designation,
            'quantity' => $quantity,
            'unit_price' => 100,
            'vat_rate' => 17,
        ]);

        return $invoice->fresh(['items', 'client']);
    }

    private function finalize(Invoice $invoice): Invoice
    {
        return app(FinalizeInvoiceAction::class)->execute($invoice);
    }

    public function test_un_brouillon_ne_bouge_aucun_stock(): void
    {
        $product = $this->trackedProduct();
        $this->draftWith($product, 5);

        $this->assertSame(0.0, $product->currentStock());
        $this->assertSame(0, StockMovement::withoutGlobalScope('user')->count());
    }

    public function test_l_emission_sort_le_stock(): void
    {
        $product = $this->trackedProduct();
        // On pose un stock de départ, puis on émet une facture de 3.
        app(StockService::class)->recordEntry($product, 10, 20.0);

        $this->finalize($this->draftWith($product, 3));

        $this->assertSame(7.0, $product->fresh()->currentStock(), '10 entrés - 3 vendus.');
        $this->assertSame(1, $product->stockMovements()->where('type', StockMovement::TYPE_SORTIE)->count());
    }

    public function test_une_ligne_sans_produit_ne_bouge_rien(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title' => 'Prestation libre',
            'quantity' => 1,
            'unit_price' => 500,
            'vat_rate' => 17,
        ]);

        $this->finalize($invoice->fresh(['items', 'client']));

        $this->assertSame(0, StockMovement::withoutGlobalScope('user')->count());
    }

    public function test_un_produit_non_suivi_ne_bouge_rien(): void
    {
        $product = $this->trackedProduct(['track_stock' => false]);

        $this->finalize($this->draftWith($product, 3));

        $this->assertSame(0, StockMovement::withoutGlobalScope('user')->count());
    }

    public function test_la_note_de_credit_reintegre_le_stock(): void
    {
        $product = $this->trackedProduct();
        app(StockService::class)->recordEntry($product, 10, 20.0);

        $invoice = $this->finalize($this->draftWith($product, 3));
        $this->assertSame(7.0, $product->fresh()->currentStock());

        // Avoir total, finalisé : les 3 reviennent en stock.
        app(CreateCreditNoteAction::class)->createFullCreditNote($invoice, 'cancellation', finalize: true);

        $this->assertSame(10.0, $product->fresh()->currentStock(), 'La note de crédit réintègre les 3.');
        $this->assertSame(1, $product->stockMovements()->where('type', StockMovement::TYPE_ENTREE)
            ->where('source_type', Invoice::class)->count());
    }

    public function test_l_emission_est_idempotente(): void
    {
        $product = $this->trackedProduct();
        $invoice = $this->finalize($this->draftWith($product, 3));

        // Rejouer l'application ne doit pas décrémenter une seconde fois.
        app(StockService::class)->applyEmission($invoice->fresh('items'));

        $this->assertSame(1, $product->stockMovements()->where('source_id', $invoice->id)->count());
    }

    public function test_l_inventaire_enregistre_l_ecart_comme_ajustement(): void
    {
        $product = $this->trackedProduct();
        app(StockService::class)->recordEntry($product, 10, 20.0);

        // Je compte 8 en rayon : écart de -2.
        app(StockService::class)->recordInventory($product, 8, note: 'Recomptage annuel');

        $this->assertSame(8.0, $product->fresh()->currentStock());
        $ajustement = $product->stockMovements()->where('type', StockMovement::TYPE_AJUSTEMENT)->first();
        $this->assertSame('-2.0000', $ajustement->quantity);
    }

    public function test_la_valeur_du_stock_est_au_cout_moyen_pondere(): void
    {
        $product = $this->trackedProduct();
        // 10 à 20 EUR puis 10 à 30 EUR -> CMP = 25 EUR.
        app(StockService::class)->recordEntry($product, 10, 20.0);
        app(StockService::class)->recordEntry($product, 10, 30.0);

        $this->assertSame(25.0, $product->weightedAverageCost());
        $this->assertSame(500.0, $product->stockValue(), '20 en stock x 25 EUR.');
    }

    public function test_le_seuil_declenche_l_alerte(): void
    {
        $product = $this->trackedProduct(['stock_alert_threshold' => 5]);
        app(StockService::class)->recordEntry($product, 4, 10.0);

        $this->assertTrue($product->fresh()->isLowOnStock());

        app(StockService::class)->recordEntry($product, 10, 10.0);
        $this->assertFalse($product->fresh()->isLowOnStock(), '14 > seuil de 5.');
    }

    public function test_la_page_stock_ne_liste_que_les_produits_suivis(): void
    {
        $suivi = $this->trackedProduct(['designation' => 'Widget suivi']);
        Product::factory()->create([
            'user_id' => $this->user->id,
            'designation' => 'Service non suivi',
            'track_stock' => false,
        ]);
        app(StockService::class)->recordEntry($suivi, 10, 20.0);

        $this->get(route('stock.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Stock/Index')
                ->where('products', fn ($rows) => count($rows) === 1
                    && $rows[0]['designation'] === 'Widget suivi'
                    && (float) $rows[0]['current_stock'] === 10.0));
    }

    public function test_l_entree_manuelle_incremente_le_stock(): void
    {
        $product = $this->trackedProduct();

        $this->post(route('stock.entry', $product->id), [
            'quantity' => 15,
            'unit_cost' => 12.5,
            'date' => now()->toDateString(),
            'note' => 'Réception',
        ])->assertSessionHasNoErrors();

        $this->assertSame(15.0, $product->fresh()->currentStock());
    }

    public function test_l_inventaire_via_le_controleur_enregistre_l_ecart(): void
    {
        $product = $this->trackedProduct();
        app(StockService::class)->recordEntry($product, 10, 20.0);

        $this->post(route('stock.inventory', $product->id), [
            'counted_quantity' => 7,
            'date' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->assertSame(7.0, $product->fresh()->currentStock());
    }

    public function test_un_mouvement_sur_un_produit_non_suivi_est_refuse(): void
    {
        $product = $this->trackedProduct(['track_stock' => false]);

        $this->post(route('stock.entry', $product->id), [
            'quantity' => 5,
            'date' => now()->toDateString(),
        ])->assertNotFound();
    }

    public function test_une_entree_manuelle_erronee_peut_etre_supprimee(): void
    {
        $product = $this->trackedProduct();
        $entry = app(StockService::class)->recordEntry($product, 100, 20.0, note: 'Erreur de saisie');
        $this->assertSame(100.0, $product->fresh()->currentStock());

        $this->delete(route('stock.movements.destroy', [$product->id, $entry->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame(0.0, $product->fresh()->currentStock(), 'Le stock est recalculé après suppression.');
        $this->assertDatabaseMissing('stock_movements', ['id' => $entry->id]);
    }

    public function test_un_mouvement_issu_d_une_facture_ne_peut_pas_etre_supprime(): void
    {
        $product = $this->trackedProduct();
        app(StockService::class)->recordEntry($product, 10, 20.0);
        $invoice = $this->finalize($this->draftWith($product, 3));

        $sortie = $product->stockMovements()->where('source_type', Invoice::class)->first();

        $this->delete(route('stock.movements.destroy', [$product->id, $sortie->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('stock_movements', ['id' => $sortie->id]);
    }

    public function test_une_depense_alimente_le_stock(): void
    {
        $product = $this->trackedProduct();
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        // Achat de 100 unités pour 800 EUR HT -> coût unitaire 8 EUR.
        $this->post(route('expenses.store'), [
            'date' => '2026-03-10',
            'provider_name' => 'Grossiste',
            'category' => 'other',
            'amount_input_mode' => 'ht',
            'amount_ht' => 800,
            'vat_rate' => 17,
            'vat_regime' => 'national',
            'is_deductible' => true,
            'stock_product_id' => $product->id,
            'stock_quantity' => 100,
        ])->assertRedirect(route('expenses.index'));

        $product->refresh();
        $this->assertSame(100.0, $product->currentStock());
        $this->assertSame(8.0, $product->weightedAverageCost(), 'Coût unitaire = 800 / 100.');
    }

    public function test_un_produit_sans_quantite_est_refuse(): void
    {
        // Relevé en revue : un produit désigné sans quantité passait la
        // validation et n'entrait rien en stock, en silence.
        $product = $this->trackedProduct();

        $this->post(route('expenses.store'), [
            'date' => '2026-03-10', 'provider_name' => 'Grossiste', 'category' => 'other',
            'amount_input_mode' => 'ht', 'amount_ht' => 800, 'vat_rate' => 17,
            'vat_regime' => 'national', 'is_deductible' => true,
            'stock_product_id' => $product->id,
            // stock_quantity volontairement absent
        ])->assertSessionHasErrors('stock_quantity');

        $this->assertSame(0.0, $product->fresh()->currentStock());
    }

    public function test_modifier_la_depense_resynchronise_le_stock(): void
    {
        $product = $this->trackedProduct();
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $this->post(route('expenses.store'), [
            'date' => '2026-03-10', 'provider_name' => 'Grossiste', 'category' => 'other',
            'amount_input_mode' => 'ht', 'amount_ht' => 800, 'vat_rate' => 17,
            'vat_regime' => 'national', 'is_deductible' => true,
            'stock_product_id' => $product->id, 'stock_quantity' => 100,
        ]);
        $expense = Expense::latest('id')->first();
        $this->assertSame(100.0, $product->fresh()->currentStock());

        // Correction : finalement 120 unités reçues.
        $this->put(route('expenses.update', $expense->id), [
            'date' => '2026-03-10', 'provider_name' => 'Grossiste', 'category' => 'other',
            'amount_input_mode' => 'ht', 'amount_ht' => 800, 'vat_rate' => 17,
            'vat_regime' => 'national', 'is_deductible' => true,
            'stock_product_id' => $product->id, 'stock_quantity' => 120,
        ]);

        $this->assertSame(120.0, $product->fresh()->currentStock(), 'Le stock suit la dépense corrigée, sans doublon.');
        $this->assertSame(1, $product->stockMovements()->where('source_type', Expense::class)->count());
    }

    public function test_le_stock_ne_fuit_jamais_chez_un_autre_compte(): void
    {
        $product = $this->trackedProduct();
        app(StockService::class)->recordEntry($product, 10, 20.0);

        // Un autre utilisateur ne voit aucun mouvement via le scope.
        $autre = User::factory()->create();
        $this->actingAs($autre);

        $this->assertSame(0, StockMovement::count(), 'Le scope isole les mouvements par compte.');
    }
}
