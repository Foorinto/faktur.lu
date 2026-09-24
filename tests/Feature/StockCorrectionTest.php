<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Corriger et valoriser le stock (FEAT-128, retours de terrain).
 *
 * Un mouvement saisi à la main se corrige sans l'effacer ; un mouvement issu
 * d'une facture ou d'une dépense reste immuable, comme sa source. Les entrées
 * oubliées sans coût se valorisent en lot, déclinaisons comprises, sans
 * toucher aux coûts déjà saisis. Et le dernier coût connu est proposé d'office.
 */
class StockCorrectionTest extends TestCase
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

    private function trackedProduct(array $attrs = [], ?User $owner = null): Product
    {
        return Product::factory()->create(array_merge([
            'user_id' => ($owner ?? $this->user)->id,
            'type' => Product::TYPE_PRODUCT,
            'track_stock' => true,
        ], $attrs));
    }

    private function entry(Product $product, float $quantity, ?float $cost, string $date = '2026-03-01', array $attrs = []): StockMovement
    {
        return StockMovement::create(array_merge([
            'user_id' => $product->user_id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'type' => StockMovement::TYPE_ENTREE,
            'date' => $date,
            'unit_cost' => $cost,
        ], $attrs));
    }

    // ---------------------------------------------------------------
    // Corriger un mouvement
    // ---------------------------------------------------------------

    public function test_une_entree_manuelle_se_corrige_et_le_stock_suit(): void
    {
        $product = $this->trackedProduct();
        $entree = $this->entry($product, 10, 2.0);

        $this->put(route('stock.movements.update', [$product->id, $entree->id]), [
            'quantity' => 7,
            'unit_cost' => 3.5,
            'date' => '2026-03-02',
            'note' => 'Erreur de saisie corrigée',
        ])->assertRedirect()->assertSessionHas('success', __('app.stock.flash_movement_updated'));

        $entree->refresh();
        $this->assertSame(7.0, (float) $entree->quantity);
        $this->assertSame(3.5, (float) $entree->unit_cost);
        $this->assertSame('2026-03-02', $entree->date->toDateString());
        $this->assertSame('Erreur de saisie corrigée', $entree->note);
        $this->assertSame(7.0, $product->currentStock());
        $this->assertSame(3.5, $product->weightedAverageCost());
    }

    public function test_le_cout_d_une_entree_peut_etre_retire(): void
    {
        $product = $this->trackedProduct();
        $entree = $this->entry($product, 4, 9.0);

        $this->put(route('stock.movements.update', [$product->id, $entree->id]), [
            'quantity' => 4,
            'unit_cost' => '',
            'date' => '2026-03-01',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertNull($entree->refresh()->unit_cost);
    }

    public function test_la_correction_refuse_une_quantite_nulle_et_une_date_future(): void
    {
        $product = $this->trackedProduct();
        $entree = $this->entry($product, 10, 2.0);

        $this->put(route('stock.movements.update', [$product->id, $entree->id]), [
            'quantity' => 0,
            'date' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors([
            'quantity',
            // Pas de « antérieure ou égale au today » à l'écran.
            'date' => __('app.stock.date_in_future'),
        ]);

        $this->assertSame(10.0, (float) $entree->refresh()->quantity);
    }

    public function test_un_mouvement_issu_d_une_facture_reste_immuable(): void
    {
        $product = $this->trackedProduct();
        $sortie = StockMovement::create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'quantity' => -2,
            'type' => StockMovement::TYPE_SORTIE,
            'date' => '2026-03-01',
            'source_type' => Invoice::class,
            'source_id' => 1,
        ]);

        $this->put(route('stock.movements.update', [$product->id, $sortie->id]), [
            'date' => '2026-03-05',
            'note' => 'tentative',
        ])->assertForbidden();

        $this->assertSame('2026-03-01', $sortie->refresh()->date->toDateString());
    }

    public function test_la_quantite_d_un_ajustement_ne_se_retouche_pas(): void
    {
        $product = $this->trackedProduct();
        $ajustement = StockMovement::create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'quantity' => -3,
            'type' => StockMovement::TYPE_AJUSTEMENT,
            'date' => '2026-03-01',
        ]);

        $this->put(route('stock.movements.update', [$product->id, $ajustement->id]), [
            'quantity' => 100,
            'unit_cost' => 50,
            'date' => '2026-03-04',
            'note' => 'Casse',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $ajustement->refresh();
        $this->assertSame(-3.0, (float) $ajustement->quantity);
        $this->assertNull($ajustement->unit_cost);
        $this->assertSame('2026-03-04', $ajustement->date->toDateString());
        $this->assertSame('Casse', $ajustement->note);
    }

    public function test_le_mouvement_d_un_autre_compte_ou_d_un_autre_article_est_introuvable(): void
    {
        $autre = User::factory()->create(['email_verified_at' => now()]);
        $sien = $this->trackedProduct([], $autre);
        $mouvementDAutrui = $this->entry($sien, 5, 1.0);

        $this->put(route('stock.movements.update', [$sien->id, $mouvementDAutrui->id]), [
            'quantity' => 1, 'date' => '2026-03-01',
        ])->assertNotFound();

        $mien = $this->trackedProduct();
        $this->put(route('stock.movements.update', [$mien->id, $mouvementDAutrui->id]), [
            'quantity' => 1, 'date' => '2026-03-01',
        ])->assertNotFound();

        $this->assertSame(5.0, (float) $mouvementDAutrui->refresh()->quantity);
    }

    // ---------------------------------------------------------------
    // Valoriser en lot
    // ---------------------------------------------------------------

    public function test_la_valorisation_en_lot_ne_touche_que_les_entrees_manuelles_sans_cout(): void
    {
        $famille = $this->trackedProduct();
        $sansCout = $this->entry($famille, 10, null);
        $dejaValorisee = $this->entry($famille, 5, 5.0);
        $issueDUneDepense = $this->entry($famille, 2, null, '2026-03-02', ['source_type' => 'expense', 'source_id' => 1]);
        $sortie = StockMovement::create([
            'user_id' => $this->user->id, 'product_id' => $famille->id, 'quantity' => -1,
            'type' => StockMovement::TYPE_SORTIE, 'date' => '2026-03-03',
        ]);

        $declinaison = $this->trackedProduct(['parent_id' => $famille->id, 'variant_label' => 'Rouge']);
        $entreeDeclinaison = $this->entry($declinaison, 3, null);

        $declinaisonNonSuivie = $this->trackedProduct(['parent_id' => $famille->id, 'variant_label' => 'Bleu', 'track_stock' => false]);
        $entreeNonSuivie = $this->entry($declinaisonNonSuivie, 3, null);

        $autreArticle = $this->trackedProduct();
        $entreeAutreArticle = $this->entry($autreArticle, 1, null);

        $autre = User::factory()->create(['email_verified_at' => now()]);
        $entreeDAutrui = $this->entry($this->trackedProduct([], $autre), 1, null);

        // L'écran envoie la famille et la déclinaison cochées ; la déclinaison
        // non suivie et l'article d'autrui ne sont pas cochés (ou pas à soi).
        $this->post(route('stock.value'), [
            'product_ids' => [$famille->id, $declinaison->id, $entreeDAutrui->product_id],
            'unit_cost' => 4,
        ])->assertRedirect()->assertSessionHas('success', __('app.stock.flash_entries_valued', [
            'count' => 2, 'cost' => '4,00 €',
        ]));

        $this->assertSame(4.0, (float) $sansCout->refresh()->unit_cost);
        $this->assertSame(4.0, (float) $entreeDeclinaison->refresh()->unit_cost);
        $this->assertSame(5.0, (float) $dejaValorisee->refresh()->unit_cost);
        $this->assertNull($issueDUneDepense->refresh()->unit_cost);
        $this->assertNull($sortie->refresh()->unit_cost);
        $this->assertNull($entreeNonSuivie->refresh()->unit_cost);
        $this->assertNull($entreeAutreArticle->refresh()->unit_cost);
        $this->assertNull($entreeDAutrui->refresh()->unit_cost);
    }

    public function test_la_valorisation_ne_s_applique_qu_aux_articles_coches(): void
    {
        $famille = $this->trackedProduct();
        $entreeFamille = $this->entry($famille, 10, null);
        $rouge = $this->trackedProduct(['parent_id' => $famille->id, 'variant_label' => 'Rouge']);
        $entreeRouge = $this->entry($rouge, 3, null);
        $bleu = $this->trackedProduct(['parent_id' => $famille->id, 'variant_label' => 'Bleu']);
        $entreeBleu = $this->entry($bleu, 2, null);

        // La famille seule : ses déclinaisons ne bougent pas, rien n'est
        // ajouté en coulisses. C'est l'écran qui coche les déclinaisons.
        $this->post(route('stock.value'), ['product_ids' => [$famille->id], 'unit_cost' => 4])
            ->assertSessionHas('success', __('app.stock.flash_entries_valued', ['count' => 1, 'cost' => '4,00 €']));
        $this->assertSame(4.0, (float) $entreeFamille->refresh()->unit_cost);
        $this->assertNull($entreeRouge->refresh()->unit_cost);
        $this->assertNull($entreeBleu->refresh()->unit_cost);

        // Une déclinaison seule, à son propre coût : sa sœur reste intacte.
        $this->post(route('stock.value'), ['product_ids' => [$bleu->id], 'unit_cost' => 6.5])
            ->assertSessionHas('success', __('app.stock.flash_entries_valued', ['count' => 1, 'cost' => '6,50 €']));
        $this->assertSame(6.5, (float) $entreeBleu->refresh()->unit_cost);
        $this->assertNull($entreeRouge->refresh()->unit_cost);
    }

    public function test_la_valorisation_exige_un_cout_et_des_articles_du_compte(): void
    {
        $product = $this->trackedProduct();
        $this->entry($product, 1, null);

        // Le message nomme le champ comme à l'écran, pas « unit cost ».
        $this->post(route('stock.value'), ['product_ids' => [$product->id]])
            ->assertSessionHasErrors(['unit_cost' => __('validation.required', ['attribute' => __('app.stock.unit_cost')])]);
        $this->post(route('stock.value'), ['product_ids' => [], 'unit_cost' => 2])
            ->assertSessionHasErrors('product_ids');

        $autre = User::factory()->create(['email_verified_at' => now()]);
        $sien = $this->trackedProduct([], $autre);
        $this->post(route('stock.value'), ['product_ids' => [$sien->id], 'unit_cost' => 2])
            ->assertNotFound();
    }

    // ---------------------------------------------------------------
    // Ce que les écrans reçoivent
    // ---------------------------------------------------------------

    public function test_la_liste_du_stock_expose_le_dernier_cout_et_les_entrees_sans_cout(): void
    {
        $product = $this->trackedProduct();
        $this->entry($product, 1, 2.0, '2026-03-01');
        $this->entry($product, 1, 3.0, '2026-03-05');
        $this->entry($product, 1, null, '2026-03-10');
        $this->entry($product, 1, null, '2026-03-11', ['source_type' => 'expense', 'source_id' => 1]);

        $this->get(route('stock.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Stock/Index')
            ->where('products.0.last_unit_cost', fn ($v) => (float) $v === 3.0)
            ->where('products.0.unvalued_entries', 1));
    }

    public function test_la_liste_du_stock_n_a_pas_de_dernier_cout_sans_entree_valorisee(): void
    {
        $product = $this->trackedProduct();
        $this->entry($product, 1, null);

        $this->get(route('stock.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('products.0.last_unit_cost', null)
            ->where('products.0.unvalued_entries', 1));
    }

    public function test_la_page_des_mouvements_compte_les_entrees_sans_cout_de_la_famille(): void
    {
        $famille = $this->trackedProduct();
        $this->entry($famille, 1, null);
        $this->entry($famille, 1, 6.0);
        $declinaison = $this->trackedProduct(['parent_id' => $famille->id, 'variant_label' => 'Rouge']);
        $this->entry($declinaison, 1, null);

        $this->get(route('stock.movements', $famille->id))->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Stock/Movements')
            ->where('unvalued_count', 2));
    }
}
