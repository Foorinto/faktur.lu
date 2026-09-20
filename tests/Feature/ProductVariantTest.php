<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->assertSame('Extensions GL30 — nuance 12', $variante->displayName());
        $this->assertSame('Extensions GL30', $famille->displayName());
    }

    public function test_l_axe_est_remonte_depuis_la_famille(): void
    {
        $famille = $this->famille(['variant_axis_label' => 'Nuance']);
        $this->post(route('products.variants.store', $famille), ['labels' => 'nuance 12']);

        $this->assertSame('Nuance', $famille->fresh()->variants->first()->axisLabel());
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
}
