<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\PurchaseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cohérence entre les lignes listées et les totaux affichés.
 *
 * Les deux requêtes étaient construites séparément dans ExpenseController :
 * le filtre « fournisseur » s'appliquait à la liste mais pas au récapitulatif,
 * si bien que les totaux annonçaient des montants que les lignes visibles ne
 * justifiaient pas.
 */
class ExpenseFilterTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        return $user;
    }

    /**
     * Le modèle recalcule TVA et TTC au `saving` à partir du HT et du taux :
     * on pose donc le taux, pas le résultat.
     */
    private function expense(User $user, array $attributes): Expense
    {
        return Expense::factory()->create(array_merge([
            'user_id' => $user->id,
            'date' => '2026-03-15',
            'vat_rate' => 17,
        ], $attributes));
    }

    public function test_les_totaux_suivent_le_filtre_fournisseur(): void
    {
        $user = $this->user();

        $this->expense($user, ['provider_name' => 'Bureau SARL', 'amount_ht' => 100]);
        $this->expense($user, ['provider_name' => 'Autre Fournisseur', 'amount_ht' => 900]);

        $this->get(route('expenses.index', ['provider' => 'Bureau']))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                // Une seule ligne listée…
                ->where('summary.count', 1)
                // …et des totaux qui la reflètent, pas la somme des deux.
                ->where('summary.total_ht', fn ($v) => (float) $v === 100.0)
                ->where('summary.total_ttc', fn ($v) => (float) $v === 117.0)
            );
    }

    public function test_les_totaux_suivent_le_filtre_categorie(): void
    {
        $user = $this->user();

        $this->expense($user, ['category' => Expense::CATEGORY_OFFICE, 'amount_ht' => 50]);
        $this->expense($user, ['category' => Expense::CATEGORY_TRAVEL, 'amount_ht' => 200]);

        $this->get(route('expenses.index', ['category' => Expense::CATEGORY_OFFICE]))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('summary.count', 1)
                ->where('summary.total_ht', fn ($v) => (float) $v === 50.0)
            );
    }

    public function test_les_filtres_se_combinent_sur_les_totaux(): void
    {
        $user = $this->user();

        $this->expense($user, ['provider_name' => 'Bureau SARL', 'category' => Expense::CATEGORY_OFFICE, 'amount_ht' => 40]);
        $this->expense($user, ['provider_name' => 'Bureau SARL', 'category' => Expense::CATEGORY_TRAVEL, 'amount_ht' => 60]);
        $this->expense($user, ['provider_name' => 'Autre', 'category' => Expense::CATEGORY_OFFICE, 'amount_ht' => 500]);

        $this->get(route('expenses.index', ['provider' => 'Bureau', 'category' => Expense::CATEGORY_OFFICE]))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('summary.count', 1)
                ->where('summary.total_ht', fn ($v) => (float) $v === 40.0)
            );
    }

    public function test_une_categorie_creee_par_l_utilisateur_est_filtrable(): void
    {
        $user = $this->user();
        PurchaseCategory::ensureDefaultsFor($user);

        PurchaseCategory::create([
            'user_id' => $user->id,
            'key' => 'loyer',
            'label' => 'Loyer',
            'pcn_account' => '6111',
            'sort_order' => 100,
        ]);

        $this->expense($user, ['category' => 'loyer', 'amount_ht' => 1200]);
        $this->expense($user, ['category' => Expense::CATEGORY_OFFICE, 'amount_ht' => 30]);

        $this->get(route('expenses.index', ['category' => 'loyer']))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('summary.count', 1)
                ->where('summary.total_ht', fn ($v) => (float) $v === 1200.0)
            );
    }
}
