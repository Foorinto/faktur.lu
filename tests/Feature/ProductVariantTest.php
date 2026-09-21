<?php

namespace Tests\Feature;

use App\Actions\FinalizeInvoiceAction;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Import\ImportSession;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Import\ProductImportService;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Variantes d'article (FEAT-120), lot 1 : le modèle et le catalogue.
 *
 * Une variante EST un article, avec un parent. Ce qui est protégé ici : le
 * catalogue ne se noie pas sous les variantes, la profondeur reste à un, une
 * famille ne se supprime pas en emportant l'historique de stock de ses
 * variantes, et un article sans variante se comporte exactement comme avant.
 */
class ProductVariantTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlansSeeder::class);

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
        $this->actingAs($this->user);
    }

    private function famille(array $surcharge = []): Product
    {
        return Product::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'designation' => 'Extensions GL30',
            'reference' => 'GL30',
            'type' => Product::TYPE_PRODUCT,
            'unit_price_ht' => 6,
            'vat_rate' => 17,
        ], $surcharge));
    }

    // --- Création en lot ------------------------------------------------------

    public function test_une_liste_collee_devient_autant_de_variantes(): void
    {
        $famille = $this->famille();

        $this->post(route('products.variants.store', $famille), [
            'variant_axis_label' => 'Nuance',
            'labels' => "nuance 1\nnuance 2\nnuance 3",
        ])->assertRedirect(route('products.index'));

        $variantes = $famille->fresh()->variants;

        $this->assertCount(3, $variantes);
        $this->assertSame('Nuance', $famille->fresh()->variant_axis_label);
        $this->assertSame(['nuance 1', 'nuance 2', 'nuance 3'], $variantes->pluck('variant_label')->all());
    }

    public function test_l_ordre_colle_est_l_ordre_affiche(): void
    {
        // Des tailles se lisent S, M, L, XL. Par ordre alphabétique on
        // obtiendrait L, M, S, XL, ce qui n'a aucun sens.
        $famille = $this->famille(['designation' => 'T-shirt', 'reference' => 'TS']);

        $this->post(route('products.variants.store', $famille), [
            'variant_axis_label' => 'Taille',
            'labels' => "S\nM\nL\nXL",
        ]);

        $this->assertSame(['S', 'M', 'L', 'XL'], $famille->fresh()->variants->pluck('variant_label')->all());
    }

    public function test_les_lignes_vides_et_les_doublons_sont_ignores(): void
    {
        $famille = $this->famille();

        $this->post(route('products.variants.store', $famille), [
            'labels' => "nuance 1\n\n  \nnuance 2\nnuance 1\n",
        ]);

        $this->assertCount(2, $famille->fresh()->variants);
    }

    public function test_relancer_la_creation_n_ajoute_pas_de_doublon(): void
    {
        $famille = $this->famille();

        $this->post(route('products.variants.store', $famille), ['labels' => "nuance 1\nnuance 2"]);
        $this->post(route('products.variants.store', $famille), ['labels' => "nuance 2\nnuance 3"]);

        $this->assertSame(['nuance 1', 'nuance 2', 'nuance 3'], $famille->fresh()->variants->pluck('variant_label')->all());
    }

    public function test_la_variante_herite_des_reglages_et_derive_sa_reference(): void
    {
        $famille = $this->famille(['unit_price_ht' => 6, 'vat_rate' => 17, 'pcn_account' => '21830']);

        $this->post(route('products.variants.store', $famille), ['labels' => 'nuance 12']);

        $variante = $famille->fresh()->variants->first();

        $this->assertSame('GL30-nuance-12', $variante->reference);
        $this->assertEquals('6.0000', $variante->unit_price_ht);
        $this->assertEquals('17.00', $variante->vat_rate);
        $this->assertSame('21830', $variante->pcn_account);
        $this->assertSame(Product::TYPE_PRODUCT, $variante->type);
    }

    // --- Propagation ----------------------------------------------------------

    public function test_propager_le_prix_evite_de_le_changer_quarante_cinq_fois(): void
    {
        $famille = $this->famille();
        $this->post(route('products.variants.store', $famille), ['labels' => "a\nb\nc"]);

        $famille->update(['unit_price_ht' => 9.5, 'vat_rate' => 8]);

        $this->post(route('products.variants.propagate', $famille))->assertRedirect();

        foreach ($famille->fresh()->variants as $variante) {
            $this->assertEquals('9.5000', $variante->unit_price_ht);
            $this->assertEquals('8.00', $variante->vat_rate);
        }
    }

    // --- Le catalogue ne se noie pas -----------------------------------------

    public function test_le_catalogue_liste_les_familles_pas_les_variantes(): void
    {
        $famille = $this->famille();
        $this->post(route('products.variants.store', $famille), ['labels' => "a\nb\nc\nd\ne"]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.variants_count', 5)
                ->has('products.data.0.variants', 5)
                // L'onglet doit annoncer ce que la page montre, pas 6.
                ->where('typeCounts.all', 1)
            );
    }

    // --- Les garde-fous -------------------------------------------------------

    public function test_une_variante_ne_peut_pas_avoir_de_variantes(): void
    {
        $famille = $this->famille();
        $this->post(route('products.variants.store', $famille), ['labels' => 'nuance 1']);
        $variante = $famille->fresh()->variants->first();

        $this->from(route('products.create'))
            ->post(route('products.store'), [
                'designation' => 'Sous-variante',
                'parent_id' => $variante->id,
                'variant_label' => 'plus petite encore',
                'unit_price_ht' => 3,
                'vat_rate' => 17,
            ])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_une_famille_ne_peut_pas_devenir_une_variante(): void
    {
        $famille = $this->famille();
        $this->post(route('products.variants.store', $famille), ['labels' => 'nuance 1']);
        $autre = $this->famille(['designation' => 'Autre', 'reference' => 'AUT']);

        $this->put(route('products.update', $famille), [
            'designation' => $famille->designation,
            'parent_id' => $autre->id,
            'variant_label' => 'rattachée',
            'unit_price_ht' => 6,
            'vat_rate' => 17,
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_une_variante_exige_un_libelle(): void
    {
        $famille = $this->famille();

        $this->post(route('products.store'), [
            'designation' => 'Sans libellé',
            'parent_id' => $famille->id,
            'unit_price_ht' => 6,
            'vat_rate' => 17,
        ])->assertSessionHasErrors('variant_label');
    }

    public function test_le_parent_d_un_autre_compte_est_refuse(): void
    {
        $autre = User::factory()->create();
        $sienne = Product::factory()->create(['user_id' => $autre->id]);

        $this->post(route('products.store'), [
            'designation' => 'Variante volée',
            'parent_id' => $sienne->id,
            'variant_label' => 'x',
            'unit_price_ht' => 6,
            'vat_rate' => 17,
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_une_famille_ne_se_supprime_pas_tant_qu_elle_porte_des_variantes(): void
    {
        // Emporter les variantes emporterait leurs mouvements de stock, donc des
        // écritures qui justifient un inventaire.
        $famille = $this->famille();
        $this->post(route('products.variants.store', $famille), ['labels' => "a\nb"]);

        $this->delete(route('products.destroy', $famille))->assertSessionHas('error');

        $this->assertNotNull($famille->fresh());
        $this->assertCount(2, $famille->fresh()->variants);
    }

    public function test_un_article_sans_variante_se_supprime_comme_avant(): void
    {
        $article = $this->famille();

        $this->delete(route('products.destroy', $article))->assertRedirect(route('products.index'));

        $this->assertNull(Product::find($article->id));
    }

    // --- Le nom porté par un document ----------------------------------------

    public function test_le_nom_affiche_porte_la_famille_et_la_variante(): void
    {
        // « T-shirt » sans la taille ne permet ni de livrer, ni d'échanger.
        $famille = $this->famille();
        $this->post(route('products.variants.store', $famille), ['labels' => 'nuance 12']);

        $variante = $famille->fresh()->variants->first();

        $this->assertSame('Extensions GL30 - nuance 12', $variante->displayName());
        $this->assertSame('Extensions GL30', $famille->displayName());
    }

    public function test_l_axe_est_remonte_depuis_la_famille(): void
    {
        $famille = $this->famille(['variant_axis_label' => 'Nuance']);
        $this->post(route('products.variants.store', $famille), ['labels' => 'nuance 12']);

        $this->assertSame('Nuance', $famille->fresh()->variants->first()->axisLabel());
    }

    // --- Les documents : le choix en deux temps -------------------------------

    public function test_la_recherche_ne_renvoie_jamais_une_variante_nue(): void
    {
        // ⚠️ Le critère de performance de la fiche : taper « GL30 » doit
        // renvoyer UNE ligne, pas quarante-cinq. La saisie d'une facture ne
        // doit pas ralentir à cause des variantes.
        $famille = $this->famille();
        $this->post(route('products.variants.store', $famille), ['labels' => "a\nb\nc\nd\ne"]);

        $reponse = $this->getJson(route('products.search', ['q' => 'Extensions']));

        $reponse->assertOk();
        $this->assertCount(1, $reponse->json('products'));
        $this->assertSame(5, $reponse->json('products.0.variants_count'));
    }

    public function test_la_recherche_trouve_une_variante_par_sa_propre_reference(): void
    {
        // C'est le code que l'utilisateur a sous les yeux sur son inventaire.
        $famille = $this->famille();
        $this->post(route('products.variants.store', $famille), ['labels' => 'nuance 12']);

        $reponse = $this->getJson(route('products.search', ['q' => 'GL30-nuance-12']));

        $trouve = collect($reponse->json('products'))->firstWhere('variant_label', 'nuance 12');

        $this->assertNotNull($trouve);
        $this->assertSame('Extensions GL30 - nuance 12', $trouve['display_name']);
    }

    public function test_les_declinaisons_d_une_famille_se_listent_a_part(): void
    {
        $famille = $this->famille(['variant_axis_label' => 'Nuance']);
        $this->post(route('products.variants.store', $famille), ['labels' => "n1\nn2"]);

        $reponse = $this->getJson(route('products.variants.list', $famille));

        $reponse->assertOk();
        $this->assertSame('Nuance', $reponse->json('axis'));
        $this->assertCount(2, $reponse->json('variants'));
        $this->assertSame('Extensions GL30 - n1', $reponse->json('variants.0.display_name'));
    }

    public function test_un_article_sans_variante_reste_directement_selectionnable(): void
    {
        $this->famille(['designation' => 'Écran 27 pouces', 'reference' => 'ECR']);

        $reponse = $this->getJson(route('products.search', ['q' => 'Écran']));

        $this->assertCount(1, $reponse->json('products'));
        $this->assertSame(0, $reponse->json('products.0.variants_count'));
    }

    public function test_facturer_une_variante_sort_son_stock_a_elle(): void
    {
        $famille = $this->famille(['track_stock' => true]);
        $this->post(route('products.variants.store', $famille), ['labels' => "n1\nn2"]);

        [$n1, $n2] = $famille->fresh()->variants->all();

        BusinessSettings::factory()->create(['user_id' => $this->user->id]);
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $facture = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);
        $facture->items()->create([
            'product_id' => $n1->id,
            'title' => $n1->displayName(),
            'quantity' => 4,
            'unit_price' => 6,
            'vat_rate' => 17,
            'total_ht' => 24, 'total_vat' => 4.08, 'total_ttc' => 28.08,
        ]);

        app(FinalizeInvoiceAction::class)->execute($facture->fresh());

        // La nuance vendue baisse, sa soeur ne bouge pas, la famille non plus.
        $this->assertSame(-4.0, $n1->fresh()->currentStock());
        $this->assertSame(0.0, $n2->fresh()->currentStock());
        $this->assertSame(0.0, $famille->fresh()->currentStock());
    }

    // --- Le stock nomme la déclinaison ---------------------------------------

    public function test_le_stock_nomme_la_declinaison_pas_la_famille(): void
    {
        // Savoir que « Extensions GL30 » est bas ne dit pas quoi commander.
        $famille = $this->famille(['track_stock' => true, 'stock_alert_threshold' => 5]);
        $this->post(route('products.variants.store', $famille), ['labels' => 'nuance 12']);

        $this->get(route('stock.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where(
                'products',
                fn ($lignes) => collect($lignes)->contains('designation', 'Extensions GL30 - nuance 12')
            ));
    }

    // --- Réservation par plan -------------------------------------------------

    public function test_un_compte_gratuit_ne_peut_pas_creer_de_variantes(): void
    {
        $gratuit = User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->subMonth(),
        ]);
        $famille = Product::factory()->create(['user_id' => $gratuit->id]);

        $this->actingAs($gratuit)
            ->post(route('products.variants.store', $famille), ['labels' => "a\nb"])
            ->assertRedirect();

        $this->assertCount(0, $famille->fresh()->variants);
    }

    public function test_un_compte_gratuit_garde_la_main_sur_ses_variantes_existantes(): void
    {
        // Le précédent des factures récurrentes : seule la création est
        // réservée. Le couper de son propre catalogue lui laisserait du stock
        // qu'il ne peut plus tenir.
        $famille = $this->famille();
        $this->post(route('products.variants.store', $famille), ['labels' => "a\nb"]);

        $this->user->update(['trial_ends_at' => now()->subMonth()]);
        $famille->update(['unit_price_ht' => 12]);

        $this->post(route('products.variants.propagate', $famille))->assertSessionHasNoErrors();

        $this->assertEquals('12.0000', $famille->fresh()->variants->first()->unit_price_ht);
    }

    public function test_une_prestation_porte_des_variantes_comme_un_bien(): void
    {
        // « Formation » en demi-journée ou en journée, « Conseil » junior ou
        // senior : la déclinaison n'est pas réservée aux marchandises.
        $prestation = $this->famille([
            'designation' => 'Formation',
            'reference' => 'FORM',
            'type' => Product::TYPE_SERVICE,
        ]);

        $this->post(route('products.variants.store', $prestation), [
            'variant_axis_label' => 'Durée',
            'labels' => "Demi-journée\nJournée",
        ])->assertSessionHasNoErrors();

        $variantes = $prestation->fresh()->variants;
        $this->assertCount(2, $variantes);
        $this->assertSame(Product::TYPE_SERVICE, $variantes->first()->type);
        $this->assertSame('Durée', $prestation->fresh()->variant_axis_label);
    }

    public function test_la_colonne_stock_d_une_famille_couvre_ses_declinaisons(): void
    {
        // Quarante-cinq nuances à trois pièces, c'est cent trente-cinq pièces
        // en rayon : c'est ce chiffre qu'on regarde avant de commander.
        $famille = $this->famille(['track_stock' => true]);
        $this->post(route('products.variants.store', $famille), ['labels' => "Noir\nBlanc"]);

        foreach ($famille->fresh()->variants as $variante) {
            StockMovement::create([
                'user_id' => $this->user->id,
                'product_id' => $variante->id,
                'type' => 'in',
                'quantity' => 3,
                'date' => now()->toDateString(),
            ]);
        }

        $this->get(route('stock.index'))->assertInertia(fn ($page) => $page
            ->where('products.0.variants_count', 2)
            // La colonne annonce tout ce que la famille couvre, déclinaisons
            // comprises : « combien en ai-je » appelle 6, pas 0.
            ->where('products.0.current_stock', 6)
            ->where('products.0.unallocated_stock', 0)
        );
    }

    // --- Répartir une entrée de stock ----------------------------------------

    /** @return array{0: Product, 1: Collection} */
    private function familleSuivie(): array
    {
        $famille = $this->famille(['track_stock' => true]);
        $this->post(route('products.variants.store', $famille), ['labels' => "Blanc\nVert"]);

        return [$famille->fresh(), $famille->fresh()->variants];
    }

    public function test_une_reception_se_repartit_entre_les_declinaisons(): void
    {
        // 500 souris reçues, 200 en blanc et 300 en vert : c'est le stock de
        // chaque nuance qu'on vient chercher, pas un total aveugle.
        [$famille, $variantes] = $this->familleSuivie();

        $this->post(route('stock.entry', $famille), [
            'quantity' => 500,
            'date' => now()->toDateString(),
            'unit_cost' => 4,
            'allocations' => [
                ['product_id' => $variantes[0]->id, 'quantity' => 200],
                ['product_id' => $variantes[1]->id, 'quantity' => 300],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(200, $variantes[0]->fresh()->currentStock(), 0.001);
        $this->assertEqualsWithDelta(300, $variantes[1]->fresh()->currentStock(), 0.001);
        // La famille ne reçoit rien : sa quantité serait comptée deux fois.
        $this->assertEqualsWithDelta(0, $famille->fresh()->currentStock(), 0.001);
    }

    public function test_une_declinaison_laissee_vide_ne_recoit_aucun_mouvement(): void
    {
        [$famille, $variantes] = $this->familleSuivie();

        $this->post(route('stock.entry', $famille), [
            'quantity' => 12,
            'date' => now()->toDateString(),
            'allocations' => [
                ['product_id' => $variantes[0]->id, 'quantity' => 12],
                ['product_id' => $variantes[1]->id, 'quantity' => null],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, StockMovement::where('product_id', $variantes[1]->id)->count());
    }

    public function test_sans_repartition_tout_reste_sur_la_famille(): void
    {
        // Le choix de qui ne veut pas tenir le détail par déclinaison : il
        // saisit sa réception et s'arrête là.
        [$famille, $variantes] = $this->familleSuivie();

        $this->post(route('stock.entry', $famille), [
            'quantity' => 500,
            'date' => now()->toDateString(),
            'allocations' => [
                ['product_id' => $variantes[0]->id, 'quantity' => 0],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(500, $famille->fresh()->currentStock(), 0.001);
        $this->assertEqualsWithDelta(0, $variantes[0]->fresh()->currentStock(), 0.001);
    }

    public function test_le_reliquat_non_reparti_reste_sur_la_famille(): void
    {
        // On reçoit un carton de 500 dont on ne connaît que 350 : le reste
        // attend sur la famille au lieu de disparaître.
        [$famille, $variantes] = $this->familleSuivie();

        $this->post(route('stock.entry', $famille), [
            'quantity' => 500,
            'date' => now()->toDateString(),
            'allocations' => [
                ['product_id' => $variantes[0]->id, 'quantity' => 150],
                ['product_id' => $variantes[1]->id, 'quantity' => 200],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(150, $variantes[0]->fresh()->currentStock(), 0.001);
        $this->assertEqualsWithDelta(200, $variantes[1]->fresh()->currentStock(), 0.001);
        $this->assertEqualsWithDelta(150, $famille->fresh()->currentStock(), 0.001);
        // Et le total lu à l'écran couvre bien les 500 reçus.
        $this->assertEqualsWithDelta(500, $famille->fresh()->stockDeLaFamille(), 0.001);
    }

    public function test_une_repartition_qui_depasse_le_total_est_refusee(): void
    {
        [$famille, $variantes] = $this->familleSuivie();

        $this->post(route('stock.entry', $famille), [
            'quantity' => 100,
            'date' => now()->toDateString(),
            'allocations' => [
                ['product_id' => $variantes[0]->id, 'quantity' => 80],
                ['product_id' => $variantes[1]->id, 'quantity' => 50],
            ],
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(0, StockMovement::count());
    }

    public function test_un_reliquat_sans_famille_suivie_est_refuse(): void
    {
        // Le reliquat n'aurait nulle part où aller : mieux vaut le dire que de
        // le perdre en silence.
        $famille = $this->famille(['track_stock' => false]);
        $this->post(route('products.variants.store', $famille), ['labels' => 'Blanc']);
        $variante = $famille->fresh()->variants->first();
        $variante->update(['track_stock' => true]);

        $this->post(route('stock.entry', $famille), [
            'quantity' => 100,
            'date' => now()->toDateString(),
            'allocations' => [['product_id' => $variante->id, 'quantity' => 40]],
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(0, StockMovement::count());
    }

    public function test_un_cout_propre_a_la_declinaison_l_emporte(): void
    {
        // Une taille XL ne s'achète pas au prix d'une S.
        [$famille, $variantes] = $this->familleSuivie();

        $this->post(route('stock.entry', $famille), [
            'quantity' => 20,
            'unit_cost' => 4,
            'date' => now()->toDateString(),
            'allocations' => [
                ['product_id' => $variantes[0]->id, 'quantity' => 10, 'unit_cost' => 7],
                ['product_id' => $variantes[1]->id, 'quantity' => 10],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(7, StockMovement::where('product_id', $variantes[0]->id)->value('unit_cost'), 0.001);
        // Sans coût propre, celui de la réception s'applique.
        $this->assertEqualsWithDelta(4, StockMovement::where('product_id', $variantes[1]->id)->value('unit_cost'), 0.001);
    }

    public function test_on_ne_repartit_pas_sur_la_declinaison_d_une_autre_famille(): void
    {
        [$famille] = $this->familleSuivie();

        $autre = $this->famille(['designation' => 'Souris', 'reference' => 'SOU', 'track_stock' => true]);
        $this->post(route('products.variants.store', $autre), ['labels' => 'Noire']);
        $etrangere = $autre->fresh()->variants->first();

        $this->post(route('stock.entry', $famille), [
            'quantity' => 50,
            'date' => now()->toDateString(),
            'allocations' => [['product_id' => $etrangere->id, 'quantity' => 50]],
        ])->assertNotFound();

        $this->assertSame(0, StockMovement::count());
    }

    public function test_une_repartition_est_verifiee_avant_la_moindre_ecriture(): void
    {
        // La ligne fautive est la seconde : si le contrôle se faisait au fil de
        // la boucle, la première serait déjà enregistrée. Une répartition à
        // moitié posée laisserait un stock faux que rien ne signale.
        [$famille, $variantes] = $this->familleSuivie();
        $autre = $this->famille(['designation' => 'Souris', 'reference' => 'SOU', 'track_stock' => true]);
        $this->post(route('products.variants.store', $autre), ['labels' => 'Noire']);

        $this->post(route('stock.entry', $famille), [
            'quantity' => 500,
            'date' => now()->toDateString(),
            'allocations' => [
                ['product_id' => $variantes[0]->id, 'quantity' => 200],
                ['product_id' => $autre->fresh()->variants->first()->id, 'quantity' => 300],
            ],
        ])->assertNotFound();

        $this->assertSame(0, StockMovement::count());
    }

    public function test_un_article_sans_declinaison_garde_l_entree_simple(): void
    {
        $article = $this->famille(['track_stock' => true]);

        $this->post(route('stock.entry', $article), [
            'quantity' => 40,
            'date' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(40, $article->fresh()->currentStock(), 0.001);
    }

    public function test_une_famille_non_suivie_repartit_quand_meme_sur_ses_declinaisons(): void
    {
        // Le cas normal : la famille est une étiquette, ce sont les nuances
        // qu'on compte en rayon.
        $famille = $this->famille(['track_stock' => false]);
        $this->post(route('products.variants.store', $famille), ['labels' => 'Blanc']);
        $variante = $famille->fresh()->variants->first();
        $variante->update(['track_stock' => true]);

        $this->post(route('stock.entry', $famille), [
            'quantity' => 15,
            'date' => now()->toDateString(),
            'allocations' => [['product_id' => $variante->id, 'quantity' => 15]],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(15, $variante->fresh()->currentStock(), 0.001);
    }

    public function test_la_valeur_totale_du_stock_ne_compte_pas_deux_fois_les_declinaisons(): void
    {
        // La colonne d'une famille inclut déjà ses déclinaisons : sommer les
        // lignes affichées gonflerait la valeur du stock de tout le catalogue.
        [$famille, $variantes] = $this->familleSuivie();

        $this->post(route('stock.entry', $famille), [
            'quantity' => 10,
            'unit_cost' => 5,
            'date' => now()->toDateString(),
            'allocations' => [['product_id' => $variantes[0]->id, 'quantity' => 10]],
        ]);

        $this->get(route('stock.index'))->assertInertia(fn ($page) => $page
            ->where('total_value', 50)
        );
    }

    public function test_une_famille_n_est_pas_en_rupture_si_ses_declinaisons_sont_fournies(): void
    {
        // Dire « rupture sur les souris » alors qu'il en reste 350 réparties
        // entre le blanc et le vert ferait commander pour rien.
        [$famille, $variantes] = $this->familleSuivie();
        $famille->update(['stock_alert_threshold' => 5]);

        $this->post(route('stock.entry', $famille), [
            'quantity' => 350,
            'date' => now()->toDateString(),
            'allocations' => [
                ['product_id' => $variantes[0]->id, 'quantity' => 150],
                ['product_id' => $variantes[1]->id, 'quantity' => 200],
            ],
        ]);

        $this->assertFalse($famille->fresh()->isLowOnStock());
    }

    // --- Réordonner ----------------------------------------------------------

    public function test_une_declinaison_remonte_d_un_rang(): void
    {
        $famille = $this->famille();
        $this->post(route('products.variants.store', $famille), ['labels' => "S\nM\nL"]);
        $troisieme = $famille->variants()->get()[2];

        $this->post(route('products.variants.reorder', $famille), [
            'variant_id' => $troisieme->id,
            'direction' => 'up',
        ])->assertSessionHasNoErrors();

        $this->assertSame(['S', 'L', 'M'], $famille->fresh()->variants->pluck('variant_label')->all());
    }

    public function test_une_declinaison_descend_d_un_rang(): void
    {
        $famille = $this->famille();
        $this->post(route('products.variants.store', $famille), ['labels' => "S\nM\nL"]);
        $premiere = $famille->variants()->first();

        $this->post(route('products.variants.reorder', $famille), [
            'variant_id' => $premiere->id,
            'direction' => 'down',
        ]);

        $this->assertSame(['M', 'S', 'L'], $famille->fresh()->variants->pluck('variant_label')->all());
    }

    public function test_remonter_la_premiere_declinaison_ne_change_rien(): void
    {
        $famille = $this->famille();
        $this->post(route('products.variants.store', $famille), ['labels' => "S\nM"]);
        $premiere = $famille->variants()->first();

        $this->post(route('products.variants.reorder', $famille), [
            'variant_id' => $premiere->id,
            'direction' => 'up',
        ])->assertSessionHasNoErrors();

        $this->assertSame(['S', 'M'], $famille->fresh()->variants->pluck('variant_label')->all());
    }

    public function test_on_ne_reordonne_pas_la_declinaison_d_une_autre_famille(): void
    {
        $famille = $this->famille();
        $this->post(route('products.variants.store', $famille), ['labels' => 'S']);

        $autre = $this->famille(['designation' => 'Autre', 'reference' => 'AUT']);
        $this->post(route('products.variants.store', $autre), ['labels' => 'XL']);
        $etrangere = $autre->variants()->first();

        $this->post(route('products.variants.reorder', $famille), [
            'variant_id' => $etrangere->id,
            'direction' => 'up',
        ])->assertNotFound();
    }

    // --- Lot 3 : import, duplication, filtre ---------------------------------

    public function test_une_colonne_variante_est_reconnue_a_l_import(): void
    {
        $mapping = app(ProductImportService::class)
            ->autoDetectMapping(['Désignation', 'Nuance', 'Prix HT']);

        $this->assertSame('variant_label', $mapping['Nuance']);
    }

    public function test_un_tableau_de_nuances_devient_une_famille_et_ses_variantes(): void
    {
        // Le cas réel : un tableur ne contient que les nuances, la famille n'y
        // figure pas en tant que ligne. Elle doit naître de la première ligne.
        $session = $this->sessionImport("designation,variante,prix\nExtensions GL30,Nuance 12,6\nExtensions GL30,Nuance 14,6\nExtensions GL30,Nuance 16,7\n");

        app(ProductImportService::class)->import($session);

        $familles = Product::topLevel()->get();
        $this->assertCount(1, $familles);
        $this->assertSame('Extensions GL30', $familles->first()->designation);

        $variantes = $familles->first()->variants;
        $this->assertCount(3, $variantes);
        $this->assertSame(['Nuance 12', 'Nuance 14', 'Nuance 16'], $variantes->pluck('variant_label')->all());
        $this->assertEquals('7.0000', $variantes->last()->unit_price_ht);
    }

    public function test_les_lignes_importees_rejoignent_une_famille_existante(): void
    {
        $famille = $this->famille();

        $session = $this->sessionImport("designation,variante,prix\nExtensions GL30,Nuance 12,6\n");
        app(ProductImportService::class)->import($session);

        $this->assertCount(1, Product::topLevel()->get());
        $this->assertSame($famille->id, Product::variantsOnly()->first()->parent_id);
    }

    public function test_une_variante_importee_n_est_pas_prise_pour_un_doublon_de_sa_famille(): void
    {
        // Sans précaution, chaque ligne porterait la désignation de la famille
        // déjà en base : toutes seraient écartées comme doublons, et l'import
        // rendrait « 0 importé » sans rien dire de plus.
        $this->famille();

        $session = $this->sessionImport("designation,variante,prix\nExtensions GL30,Nuance 12,6\nExtensions GL30,Nuance 14,6\n");
        $apercu = app(ProductImportService::class)->validateAndPreview($session);

        $this->assertCount(0, $apercu['duplicates']);
        $this->assertCount(2, $apercu['valid']);
    }

    public function test_l_apercu_annonce_la_famille_qu_il_va_creer_en_plus(): void
    {
        // Le fichier a quatre lignes, l'import en crée cinq : la famille
        // n'apparaît pas dans le tableur. L'écart doit être annoncé avant.
        $session = $this->sessionImport("designation,variante,prix\nExtensions GL30,Nuance 12,6\nExtensions GL30,Nuance 14,6\n");

        $apercu = app(ProductImportService::class)->validateAndPreview($session);

        $this->assertContains(
            __('app.import_products_notice_families', ['count' => 1]),
            $apercu['notices']
        );
    }

    public function test_l_apercu_se_tait_quand_la_famille_existe_deja(): void
    {
        $this->famille();
        $session = $this->sessionImport("designation,variante,prix\nExtensions GL30,Nuance 12,6\n");

        $apercu = app(ProductImportService::class)->validateAndPreview($session);

        $this->assertSame([], $apercu['notices']);
    }

    public function test_un_second_import_ne_duplique_pas_les_memes_nuances(): void
    {
        $service = app(ProductImportService::class);
        $csv = "designation,variante,prix\nExtensions GL30,Nuance 12,6\nExtensions GL30,Nuance 14,6\n";

        $service->import($this->sessionImport($csv));
        $service->import($this->sessionImport($csv));

        $this->assertCount(2, Product::variantsOnly()->get());
        $this->assertCount(1, Product::topLevel()->get());
    }

    public function test_un_compte_gratuit_n_importe_pas_de_variantes(): void
    {
        $gratuit = User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->subMonth(),
        ]);
        $this->actingAs($gratuit);

        $session = $this->sessionImport("designation,variante,prix\nExtensions GL30,Nuance 12,6\n", $gratuit);
        app(ProductImportService::class)->import($session);

        $this->assertCount(0, Product::variantsOnly()->get());
        $this->assertCount(1, Product::topLevel()->get());
    }

    public function test_dupliquer_une_famille_emporte_ses_declinaisons(): void
    {
        $famille = $this->famille();
        $this->post(route('products.variants.store', $famille), ['labels' => "Nuance 12\nNuance 14"]);

        $this->post(route('products.duplicate', $famille))->assertRedirect();

        $copie = Product::topLevel()->where('id', '!=', $famille->id)->first();
        $this->assertNotNull($copie);
        $this->assertSame('Extensions GL30 '.__('app.products.copy_suffix'), $copie->designation);
        $this->assertSame('GL30-'.__('app.products.copy_reference_suffix'), $copie->reference);
        $this->assertCount(2, $copie->variants);
        $this->assertSame(['Nuance 12', 'Nuance 14'], $copie->variants->pluck('variant_label')->all());
    }

    public function test_les_references_de_la_copie_ne_recouvrent_pas_l_original(): void
    {
        // Deux articles distincts qui portent le même code, c'est un inventaire
        // faux et un bon de commande ambigu.
        $famille = $this->famille();
        $this->post(route('products.variants.store', $famille), ['labels' => 'Nuance 12']);

        $this->post(route('products.duplicate', $famille));

        $references = Product::withTrashed()->pluck('reference')->filter()->all();
        $this->assertSame($references, array_unique($references));
    }

    public function test_dupliquer_une_declinaison_cree_une_voisine_pas_une_famille(): void
    {
        $famille = $this->famille();
        $this->post(route('products.variants.store', $famille), ['labels' => 'Nuance 12']);
        $variante = $famille->variants()->first();

        $this->post(route('products.duplicate', $variante));

        $this->assertCount(1, Product::topLevel()->get());
        $this->assertCount(2, $famille->fresh()->variants);
        $this->assertSame('Nuance 12 '.__('app.products.copy_suffix'), $famille->fresh()->variants->last()->variant_label);
    }

    public function test_la_copie_ne_reprend_pas_le_stock_de_l_original(): void
    {
        $famille = $this->famille(['track_stock' => true]);
        StockMovement::create([
            'user_id' => $this->user->id,
            'product_id' => $famille->id,
            'type' => 'in',
            'quantity' => 40,
            'date' => now()->toDateString(),
        ]);

        $this->post(route('products.duplicate', $famille));

        $copie = Product::topLevel()->where('id', '!=', $famille->id)->first();
        $this->assertSame(0, StockMovement::where('product_id', $copie->id)->count());
        $this->assertTrue((bool) $copie->track_stock);
    }

    public function test_le_filtre_familles_ne_montre_que_les_articles_a_declinaisons(): void
    {
        $famille = $this->famille();
        $this->post(route('products.variants.store', $famille), ['labels' => 'Nuance 12']);
        $this->famille(['designation' => 'Prestation simple', 'reference' => 'PS']);

        $reponse = $this->get(route('products.index', ['families' => 1]));

        $reponse->assertInertia(fn ($page) => $page
            ->where('products.data.0.designation', 'Extensions GL30')
            ->count('products.data', 1)
            ->where('typeCounts.families', 1)
        );
    }

    private function sessionImport(string $csv, ?User $proprietaire = null): ImportSession
    {
        Storage::fake('local');

        $chemin = 'import-'.uniqid().'.csv';
        Storage::put($chemin, $csv);

        return ImportSession::create([
            'user_id' => ($proprietaire ?? $this->user)->id,
            'type' => 'products',
            'filename' => $chemin,
            'storage_path' => $chemin,
            'mapping' => ['designation' => 'designation', 'variante' => 'variant_label', 'prix' => 'unit_price_ht'],
            'duplicate_strategy' => 'skip',
            'status' => 'preview',
        ]);
    }
}
