<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'écran des charges fixes (FEAT-117, lot 2).
 *
 * Ce que ces tests protègent : une charge se crée, se suspend et s'arrête sans
 * jamais toucher aux dépenses déjà enregistrées, et la charge d'un compte reste
 * invisible aux autres.
 */
class RecurringExpenseScreenTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function payload(array $surcharge = []): array
    {
        return array_merge([
            'label' => 'Loyer du bureau',
            'frequency' => RecurringExpense::FREQUENCY_MONTHLY,
            'next_expense_date' => '2026-10-31',
            'provider_name' => 'Immo Lux Sàrl',
            'supplier_country' => 'LU',
            'category' => Expense::CATEGORY_OTHER,
            'amount_input_mode' => Expense::INPUT_HT,
            'amount' => 1200,
            'vat_rate' => 17,
            'vat_regime' => Expense::REGIME_NATIONAL,
            'is_deductible' => true,
            'payment_method' => Expense::PAYMENT_TRANSFER,
        ], $surcharge);
    }

    public function test_la_liste_affiche_les_charges_et_leur_poids_mensuel(): void
    {
        RecurringExpense::factory()->create([
            'user_id' => $this->user->id,
            'frequency' => RecurringExpense::FREQUENCY_MONTHLY,
            'amount' => 1000,
        ]);
        RecurringExpense::factory()->create([
            'user_id' => $this->user->id,
            'frequency' => RecurringExpense::FREQUENCY_YEARLY,
            'amount' => 1200,
        ]);

        $this->get(route('recurring-expenses.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('RecurringExpenses/Index')
                ->has('charges', 2)
                // 1000 par mois + 1200 par an ramenés au mois = 1100.
                ->where('monthlyTotal', 1100)
            );
    }

    public function test_une_charge_suspendue_ne_pese_pas_dans_le_total(): void
    {
        RecurringExpense::factory()->create([
            'user_id' => $this->user->id,
            'frequency' => RecurringExpense::FREQUENCY_MONTHLY,
            'amount' => 800,
            'is_active' => false,
        ]);

        $this->get(route('recurring-expenses.index'))
            ->assertInertia(fn ($page) => $page->where('monthlyTotal', 0));
    }

    public function test_le_formulaire_se_preremplit_depuis_une_depense(): void
    {
        $depense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'provider_name' => 'POST Luxembourg',
            'date' => '2026-09-15',
        ]);

        $this->get(route('recurring-expenses.create', ['from_expense' => $depense->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('RecurringExpenses/Create')
                ->where('modele.provider_name', 'POST Luxembourg')
                ->where('modele.next_expense_date', '2026-10-15')
            );
    }

    public function test_la_depense_d_un_autre_compte_ne_preremplit_rien(): void
    {
        $autre = User::factory()->create();
        $depense = Expense::factory()->create(['user_id' => $autre->id]);

        $this->get(route('recurring-expenses.create', ['from_expense' => $depense->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('modele', null));
    }

    public function test_la_creation_memorise_le_jour_voulu(): void
    {
        $this->post(route('recurring-expenses.store'), $this->payload())
            ->assertRedirect(route('recurring-expenses.index'));

        $charge = RecurringExpense::sole();

        $this->assertSame($this->user->id, $charge->user_id);
        $this->assertSame(31, $charge->anchor_day, 'Le 31 demandé est conservé pour les mois suivants.');
        $this->assertTrue($charge->is_active);
    }

    public function test_deplacer_l_echeance_deplace_le_jour_voulu(): void
    {
        $charge = RecurringExpense::factory()->create([
            'user_id' => $this->user->id,
            'next_expense_date' => '2026-10-31',
        ]);

        $this->put(route('recurring-expenses.update', $charge), $this->payload([
            'next_expense_date' => '2026-11-05',
        ]))->assertRedirect(route('recurring-expenses.index'));

        $charge->refresh();
        $this->assertSame(5, $charge->anchor_day);
        $this->assertSame('2026-12-05', $charge->calculateNextDate()->toDateString());
    }

    public function test_une_charge_se_suspend_et_se_reprend(): void
    {
        $charge = RecurringExpense::factory()->create(['user_id' => $this->user->id]);

        $this->post(route('recurring-expenses.toggle', $charge));
        $this->assertFalse($charge->fresh()->is_active);

        $this->post(route('recurring-expenses.toggle', $charge));
        $this->assertTrue($charge->fresh()->is_active);
    }

    public function test_supprimer_la_charge_conserve_les_depenses_deja_creees(): void
    {
        $charge = RecurringExpense::factory()->create([
            'user_id' => $this->user->id,
            'next_expense_date' => now()->toDateString(),
        ]);

        $this->artisan('recurring-expenses:generate');
        $this->assertSame(1, Expense::count());

        $this->delete(route('recurring-expenses.destroy', $charge))
            ->assertRedirect(route('recurring-expenses.index'));

        $this->assertSame(0, RecurringExpense::count());
        $this->assertSame(1, Expense::count(), 'Une dépense engagée est une écriture comptable, pas une prévision.');
        $this->assertNull(Expense::sole()->recurring_expense_id, 'Seul le lien disparaît.');
    }

    public function test_la_charge_d_un_autre_compte_reste_hors_de_portee(): void
    {
        $autre = User::factory()->create();
        $charge = RecurringExpense::factory()->create(['user_id' => $autre->id]);

        $this->get(route('recurring-expenses.index'))
            ->assertInertia(fn ($page) => $page->has('charges', 0));

        $this->get(route('recurring-expenses.edit', $charge))->assertNotFound();
        $this->put(route('recurring-expenses.update', $charge), $this->payload())->assertNotFound();
        $this->post(route('recurring-expenses.toggle', $charge))->assertNotFound();
        $this->delete(route('recurring-expenses.destroy', $charge))->assertNotFound();
    }

    public function test_une_charge_sans_fournisseur_ni_categorie_est_refusee(): void
    {
        $this->from(route('recurring-expenses.create'))
            ->post(route('recurring-expenses.store'), $this->payload([
                'provider_name' => '',
                'category' => '',
            ]))
            ->assertSessionHasErrors(['provider_name', 'category']);

        $this->assertSame(0, RecurringExpense::count());
    }
}
