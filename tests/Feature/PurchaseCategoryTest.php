<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\PurchaseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Catégories de dépenses définies par l'utilisateur.
 *
 * La contrainte qui gouverne toute la conception : des dépenses sont déjà
 * enregistrées en production avec les neuf clés historiques. Le provisionnement
 * doit les laisser rattachées sans qu'aucun UPDATE ne soit exécuté sur la table
 * `expenses`.
 */
class PurchaseCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        return $user;
    }

    public function test_le_provisionnement_cree_les_neuf_categories_historiques(): void
    {
        $user = $this->user();

        PurchaseCategory::ensureDefaultsFor($user);

        $keys = PurchaseCategory::where('user_id', $user->id)->ordered()->pluck('key')->all();

        $this->assertSame(PurchaseCategory::DEFAULT_KEYS, $keys, 'Les clés et leur ordre doivent être conservés.');
        $this->assertTrue(PurchaseCategory::where('user_id', $user->id)->get()->every->is_default);
    }

    public function test_le_provisionnement_est_idempotent(): void
    {
        $user = $this->user();

        PurchaseCategory::ensureDefaultsFor($user);
        PurchaseCategory::ensureDefaultsFor($user);
        PurchaseCategory::ensureDefaultsFor($user);

        $this->assertSame(9, PurchaseCategory::where('user_id', $user->id)->count());
    }

    public function test_une_depense_existante_reste_rattachee_apres_provisionnement(): void
    {
        $user = $this->user();

        // Une dépense enregistrée AVANT l'existence de la table, comme en production.
        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'category' => Expense::CATEGORY_HARDWARE,
        ]);
        $touchedBefore = $expense->updated_at;

        PurchaseCategory::ensureDefaultsFor($user);

        $expense->refresh();

        $this->assertSame(Expense::CATEGORY_HARDWARE, $expense->category);
        $this->assertEquals($touchedBefore, $expense->updated_at, 'Le provisionnement ne doit rien écrire dans expenses.');
        $this->assertArrayHasKey(
            Expense::CATEGORY_HARDWARE,
            PurchaseCategory::mapFor($user),
            'La catégorie de la dépense doit exister dans la nouvelle table.'
        );
    }

    public function test_un_renommage_ne_change_jamais_la_cle(): void
    {
        $user = $this->user();
        PurchaseCategory::ensureDefaultsFor($user);

        $category = PurchaseCategory::where('key', 'office')->firstOrFail();
        $category->update(['label' => 'Loyer et charges']);

        $this->assertSame('office', $category->fresh()->key, 'La clé est l\'ancre de l\'historique : elle ne bouge pas.');
        $this->assertSame('Loyer et charges', PurchaseCategory::mapFor($user)['office']);
    }

    public function test_les_categories_desactivees_sortent_de_la_liste_de_saisie(): void
    {
        $user = $this->user();
        PurchaseCategory::ensureDefaultsFor($user);

        PurchaseCategory::where('key', 'hosting')->update(['is_active' => false]);

        $this->assertArrayNotHasKey('hosting', PurchaseCategory::mapFor($user));
        // …mais restent connues, pour que l'historique s'affiche encore.
        $this->assertArrayHasKey('hosting', PurchaseCategory::mapFor($user, activeOnly: false));
    }

    // --- Écran de gestion --------------------------------------------------

    public function test_creer_une_categorie_derive_une_cle_du_libelle(): void
    {
        $this->user();

        $this->post(route('settings.purchase-categories.store'), [
            'label' => 'Loyer et charges',
            'pcn_account' => '6111',
        ])->assertSessionHasNoErrors();

        $created = PurchaseCategory::where('label', 'Loyer et charges')->firstOrFail();

        $this->assertSame('loyer_et_charges', $created->key);
        $this->assertSame('6111', $created->pcn_account);
        $this->assertFalse($created->is_default);
    }

    public function test_deux_libelles_proches_ne_partagent_pas_la_meme_cle(): void
    {
        $this->user();

        $this->post(route('settings.purchase-categories.store'), ['label' => 'Loyer']);
        $this->post(route('settings.purchase-categories.store'), ['label' => 'loyer']);

        $keys = PurchaseCategory::whereIn('label', ['Loyer', 'loyer'])->pluck('key')->all();

        $this->assertCount(2, array_unique($keys), 'La contrainte unique impose des clés distinctes.');
    }

    public function test_une_categorie_utilisee_ne_peut_pas_etre_supprimee(): void
    {
        $user = $this->user();
        PurchaseCategory::ensureDefaultsFor($user);

        $category = PurchaseCategory::where('key', 'office')->firstOrFail();
        Expense::factory()->create(['user_id' => $user->id, 'category' => 'office']);

        $this->delete(route('settings.purchase-categories.destroy', $category->id))
            ->assertSessionHas('error');

        $this->assertModelExists($category);
    }

    public function test_une_categorie_inutilisee_se_supprime(): void
    {
        $user = $this->user();
        PurchaseCategory::ensureDefaultsFor($user);

        $category = PurchaseCategory::where('key', 'training')->firstOrFail();

        $this->delete(route('settings.purchase-categories.destroy', $category->id))
            ->assertSessionHas('success');

        $this->assertModelMissing($category);
    }

    public function test_la_cle_ne_bouge_pas_meme_si_le_libelle_change_du_tout_au_tout(): void
    {
        $user = $this->user();
        PurchaseCategory::ensureDefaultsFor($user);

        $category = PurchaseCategory::where('key', 'hosting')->firstOrFail();

        $this->put(route('settings.purchase-categories.update', $category->id), [
            'label' => 'Assurance responsabilité civile',
            'pcn_account' => '6146',
        ])->assertSessionHasNoErrors();

        $this->assertSame('hosting', $category->fresh()->key);
        $this->assertSame('6146', $category->fresh()->pcn_account);
    }

    public function test_la_categorie_d_un_autre_compte_est_hors_de_portee(): void
    {
        $autre = User::factory()->create();
        PurchaseCategory::ensureDefaultsFor($autre);
        $sienne = PurchaseCategory::withoutUserScope()->where('user_id', $autre->id)->where('key', 'office')->firstOrFail();

        $this->user();

        $this->put(route('settings.purchase-categories.update', $sienne->id), ['label' => 'Détournée'])
            ->assertNotFound();

        $this->assertSame('Fournitures de bureau', $sienne->fresh()->label);
    }

    public function test_une_categorie_appartient_a_son_seul_compte(): void
    {
        $autre = User::factory()->create();
        PurchaseCategory::ensureDefaultsFor($autre);
        PurchaseCategory::withoutUserScope()->where('user_id', $autre->id)->where('key', 'office')
            ->update(['label' => 'Chez le voisin']);

        $user = $this->user();
        PurchaseCategory::ensureDefaultsFor($user);

        $this->assertSame(9, PurchaseCategory::count(), 'Le scope global ne doit montrer que les siennes.');
        $this->assertNotSame('Chez le voisin', PurchaseCategory::mapFor($user)['office']);
    }
}
