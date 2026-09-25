<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Stock et dépenses récurrentes réservés à Essentiel et Pro (FEAT-136).
 *
 * Décision d'Alexandre du 2026-09-25 : tenir un stock va avec les
 * déclinaisons, la récurrence des dépenses avec celle des factures. Un
 * compte Gratuit est renvoyé vers l'abonnement pour le stock et pour créer
 * une charge fixe ; il garde la main sur ses charges héritées, qui tournent
 * sous le quota comme les factures récurrentes.
 */
class StockPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlansSeeder::class);
    }

    private function user(bool $trial): User
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'trial_ends_at' => $trial ? now()->addDays(10) : null]);
        BusinessSettings::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_les_plans_portent_les_deux_cles_des_essentiel(): void
    {
        foreach (['stock', 'recurring_expenses'] as $cle) {
            $this->assertNotContains($cle, Plan::free()->features, $cle);
            $this->assertContains($cle, Plan::essentiel()->features, $cle);
            $this->assertContains($cle, Plan::pro()->features, $cle);
        }
    }

    public function test_un_compte_gratuit_est_renvoye_vers_l_abonnement(): void
    {
        $this->actingAs($this->user(false));

        $this->get(route('stock.index'))->assertRedirect(route('subscription.index'))
            ->assertSessionHas('upgrade_required', fn ($u) => $u['feature'] === 'stock' && $u['min_plan'] === 'Essentiel');
        // Comme les factures récurrentes : la liste reste ouverte pour reprendre
        // la main sur une charge héritée, en créer une demande Essentiel.
        $this->get(route('recurring-expenses.index'))->assertOk();
        $this->get(route('recurring-expenses.create'))->assertRedirect(route('subscription.index'))
            ->assertSessionHas('upgrade_required', fn ($u) => $u['feature'] === 'recurring_expenses');
        $this->post(route('recurring-expenses.store'), [])->assertRedirect(route('subscription.index'));
        $this->post(route('stock.value'), ['product_ids' => [1], 'unit_cost' => 1])->assertRedirect(route('subscription.index'));
    }

    public function test_un_compte_en_essai_accede_aux_deux_modules(): void
    {
        $this->actingAs($this->user(true));

        $this->get(route('stock.index'))->assertOk();
        $this->get(route('recurring-expenses.create'))->assertOk();
    }
}
