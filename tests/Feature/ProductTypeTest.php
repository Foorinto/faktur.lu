<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Famille d'article (produit / prestation) et survie du catalogue existant.
 *
 * Le champ `type` a été ajouté alors qu'un utilisateur se servait déjà du
 * catalogue en production. La contrainte posée à l'ouverture du chantier était
 * de ne rien lui faire perdre : ces tests la verrouillent.
 */
class ProductTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlansSeeder::class);
    }

    private function proUser(): User
    {
        $user = User::factory()->create([
            'trial_ends_at' => now()->addDays(14),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        return $user;
    }

    public function test_un_article_anterieur_au_champ_reste_non_classe_et_visible(): void
    {
        $user = $this->proUser();

        // Créé sans type : exactement l'état des articles déjà en base.
        $ancien = Product::factory()->create(['user_id' => $user->id, 'designation' => 'Article historique']);

        $this->assertNull($ancien->fresh()->type, 'La migration ne doit reclasser aucun article.');

        $this->get(route('products.index'))
            ->assertSuccessful()
            ->assertSee('Article historique');
    }

    public function test_le_filtre_separe_les_trois_familles(): void
    {
        $user = $this->proUser();

        Product::factory()->create(['user_id' => $user->id, 'designation' => 'Micro', 'type' => Product::TYPE_PRODUCT]);
        Product::factory()->create(['user_id' => $user->id, 'designation' => 'Conseil', 'type' => Product::TYPE_SERVICE]);
        Product::factory()->create(['user_id' => $user->id, 'designation' => 'Historique', 'type' => null]);

        $this->assertSame(['Micro'], Product::ofType(Product::TYPE_PRODUCT)->pluck('designation')->all());
        $this->assertSame(['Conseil'], Product::ofType(Product::TYPE_SERVICE)->pluck('designation')->all());
        $this->assertSame(['Historique'], Product::ofType('unclassified')->pluck('designation')->all());

        // Aucun filtre : tout le catalogue, non classés compris.
        $this->assertCount(3, Product::ofType(null)->get());
    }

    public function test_la_famille_est_enregistree_depuis_le_formulaire(): void
    {
        $user = $this->proUser();

        $this->post(route('products.store'), [
            'designation' => 'Prestation de conseil',
            'unit_price_ht' => 120,
            'vat_rate' => 17,
            'unit' => 'hour',
            'type' => Product::TYPE_SERVICE,
        ])->assertSessionHasNoErrors();

        $this->assertSame(Product::TYPE_SERVICE, Product::where('designation', 'Prestation de conseil')->value('type'));
    }

    public function test_une_famille_inconnue_est_refusee(): void
    {
        $this->proUser();

        $this->post(route('products.store'), [
            'designation' => 'Bidon',
            'unit_price_ht' => 10,
            'vat_rate' => 17,
            'unit' => 'piece',
            'type' => 'autre_chose',
        ])->assertSessionHasErrors('type');
    }

    // --- Actions groupées ------------------------------------------------------

    public function test_la_modification_groupee_change_type_et_tva(): void
    {
        $user = $this->proUser();
        $a = Product::factory()->create(['user_id' => $user->id, 'type' => null, 'vat_rate' => 17]);
        $b = Product::factory()->create(['user_id' => $user->id, 'type' => null, 'vat_rate' => 17]);
        $intact = Product::factory()->create(['user_id' => $user->id, 'type' => null, 'vat_rate' => 17]);

        $this->post(route('products.bulk-update'), [
            'ids' => [$a->id, $b->id],
            'type' => Product::TYPE_SERVICE,
            'vat_rate' => 14,
        ])->assertSessionHasNoErrors();

        foreach ([$a, $b] as $p) {
            $this->assertSame(Product::TYPE_SERVICE, $p->fresh()->type);
            $this->assertEquals(14, (float) $p->fresh()->vat_rate);
        }

        // Un article hors sélection ne bouge pas.
        $this->assertNull($intact->fresh()->type);
        $this->assertEquals(17, (float) $intact->fresh()->vat_rate);
    }

    public function test_la_modification_groupee_peut_declasser(): void
    {
        $user = $this->proUser();
        $p = Product::factory()->create(['user_id' => $user->id, 'type' => Product::TYPE_PRODUCT]);

        // `type` transmis à vide = « non classé », et non « ne pas toucher ».
        $this->post(route('products.bulk-update'), ['ids' => [$p->id], 'type' => null])
            ->assertSessionHasNoErrors();

        $this->assertNull($p->fresh()->type);
    }

    public function test_la_suppression_groupee_est_douce(): void
    {
        $user = $this->proUser();
        $a = Product::factory()->create(['user_id' => $user->id]);
        $b = Product::factory()->create(['user_id' => $user->id]);

        $this->post(route('products.bulk-delete'), ['ids' => [$a->id, $b->id]])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, Product::where('user_id', $user->id)->count());
        // Récupérable : une sélection ratée n'est pas une perte définitive.
        $this->assertSame(2, Product::withTrashed()->where('user_id', $user->id)->count());
    }

    public function test_une_action_groupee_ne_touche_pas_le_catalogue_d_un_autre_compte(): void
    {
        $victime = User::factory()->create(['trial_ends_at' => now()->addDays(14), 'email_verified_at' => now()]);
        $sien = Product::factory()->create(['user_id' => $victime->id, 'type' => Product::TYPE_PRODUCT]);

        $this->proUser(); // acteur = un autre compte

        $this->post(route('products.bulk-update'), ['ids' => [$sien->id], 'type' => Product::TYPE_SERVICE]);
        $this->post(route('products.bulk-delete'), ['ids' => [$sien->id]]);

        $this->assertSame(Product::TYPE_PRODUCT, $sien->fresh()->type, 'Le type d\'un autre compte doit rester intact.');
        $this->assertNotNull($sien->fresh(), 'L\'article d\'un autre compte ne doit pas être supprimé.');
    }

    public function test_le_catalogue_survit_au_retour_sur_le_plan_gratuit(): void
    {
        // 50 articles créés pendant l'essai, puis l'essai expire.
        $user = User::factory()->create([
            'trial_ends_at' => now()->addDays(14),
            'email_verified_at' => now(),
        ]);
        Product::factory()->count(50)->create(['user_id' => $user->id]);

        $user->forceFill(['trial_ends_at' => now()->subDay()])->save();
        $this->actingAs($user->fresh());

        // Rien n'est supprimé ni masqué…
        $this->assertSame(50, Product::where('user_id', $user->id)->count());
        $this->get(route('products.index'))->assertSuccessful();

        // …et les articles restent insérables dans une facture.
        $response = $this->getJson(route('products.search', ['q' => '']));
        $response->assertSuccessful();
        $this->assertNotEmpty($response->json('products'));
    }
}
