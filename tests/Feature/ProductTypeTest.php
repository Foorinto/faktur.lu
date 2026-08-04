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
