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
