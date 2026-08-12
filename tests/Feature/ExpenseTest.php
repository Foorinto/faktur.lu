<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        // S'authentifier avant de créer les données : le scope BelongsToUser
        // attribue le propriétaire à la création.
        $this->actingAs($this->user);
    }

    public function test_guest_cannot_access_expenses(): void
    {
        auth()->logout(); // setUp() authentifie pour créer les données

        $this->get(route('expenses.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_view_expenses_index(): void
    {
        Expense::factory()->count(5)->create();

        $this->actingAs($this->user)
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Expenses/Index')
                ->has('expenses.data', 5)
                ->has('summary')
                ->has('categories')
            );
    }

    public function test_user_can_filter_expenses_by_category(): void
    {
        Expense::factory()->create(['category' => Expense::CATEGORY_HARDWARE]);
        Expense::factory()->create(['category' => Expense::CATEGORY_SOFTWARE]);
        Expense::factory()->create(['category' => Expense::CATEGORY_HARDWARE]);

        $this->actingAs($this->user)
            ->get(route('expenses.index', ['category' => Expense::CATEGORY_HARDWARE]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('expenses.data', 2)
            );
    }

    public function test_user_can_filter_expenses_by_year(): void
    {
        Expense::factory()->create(['date' => '2024-06-15']);
        Expense::factory()->create(['date' => '2024-12-01']);
        Expense::factory()->create(['date' => '2023-06-15']);

        $this->actingAs($this->user)
            ->get(route('expenses.index', ['year' => '2024']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('expenses.data', 2)
            );
    }

    public function test_user_can_view_create_expense_form(): void
    {
        $this->actingAs($this->user)
            ->get(route('expenses.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Expenses/Create')
                ->has('categories')
                ->has('vatRates')
                ->has('paymentMethods')
            );
    }

    public function test_user_can_create_expense(): void
    {
        $this->actingAs($this->user)
            ->post(route('expenses.store'), [
                'date' => now()->format('Y-m-d'),
                'provider_name' => 'Amazon',
                'category' => Expense::CATEGORY_HARDWARE,
                'amount_ht' => 250,
                'vat_rate' => 17,
                'description' => 'Clavier mécanique',
            ])
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'provider_name' => 'Amazon',
            'category' => Expense::CATEGORY_HARDWARE,
        ]);
    }

    public function test_expense_calculates_vat_and_ttc_on_create(): void
    {
        $this->actingAs($this->user)
            ->post(route('expenses.store'), [
                'date' => now()->format('Y-m-d'),
                'provider_name' => 'Test',
                'category' => Expense::CATEGORY_SOFTWARE,
                'amount_ht' => 100,
                'vat_rate' => 17,
            ]);

        $expense = Expense::first();
        $this->assertEquals('17.0000', $expense->amount_vat);
        $this->assertEquals('117.0000', $expense->amount_ttc);
    }

    public function test_user_can_view_edit_expense_form(): void
    {
        $expense = Expense::factory()->create();

        $this->actingAs($this->user)
            ->get(route('expenses.edit', $expense))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Expenses/Edit')
                ->has('expense')
                ->has('categories')
            );
    }

    public function test_user_can_update_expense(): void
    {
        $expense = Expense::factory()->create(['provider_name' => 'Old Provider']);

        $this->actingAs($this->user)
            ->put(route('expenses.update', $expense), [
                'date' => $expense->date->format('Y-m-d'),
                'provider_name' => 'New Provider',
                'category' => $expense->category,
                'amount_ht' => $expense->amount_ht,
                'vat_rate' => $expense->vat_rate,
            ])
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'provider_name' => 'New Provider',
        ]);
    }

    public function test_user_can_delete_expense(): void
    {
        $expense = Expense::factory()->create();

        $this->actingAs($this->user)
            ->delete(route('expenses.destroy', $expense))
            ->assertRedirect(route('expenses.index'));

        $this->assertSoftDeleted($expense);
    }

    public function test_user_can_view_summary(): void
    {
        Expense::factory()->count(5)->create(['date' => now()]);

        $this->actingAs($this->user)
            ->get(route('expenses.summary'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Expenses/Summary')
                ->has('yearSummary')
                ->has('monthlySummary')
                ->has('categories')
            );
    }

    public function test_validation_requires_date(): void
    {
        $this->actingAs($this->user)
            ->post(route('expenses.store'), [
                'provider_name' => 'Test',
                'category' => Expense::CATEGORY_HARDWARE,
                'amount_ht' => 100,
                'vat_rate' => 17,
            ])
            ->assertSessionHasErrors('date');
    }

    public function test_validation_requires_provider_name(): void
    {
        $this->actingAs($this->user)
            ->post(route('expenses.store'), [
                'date' => now()->format('Y-m-d'),
                'category' => Expense::CATEGORY_HARDWARE,
                'amount_ht' => 100,
                'vat_rate' => 17,
            ])
            ->assertSessionHasErrors('provider_name');
    }

    public function test_validation_requires_valid_category(): void
    {
        $this->actingAs($this->user)
            ->post(route('expenses.store'), [
                'date' => now()->format('Y-m-d'),
                'provider_name' => 'Test',
                'category' => 'invalid_category',
                'amount_ht' => 100,
                'vat_rate' => 17,
            ])
            ->assertSessionHasErrors('category');
    }

    /**
     * Le taux n'est plus restreint à la grille luxembourgeoise.
     *
     * Une dépense n'est pas une vente : la TVA payée est celle du pays du
     * fournisseur. 25 %, l'ancien exemple de « taux invalide », est simplement
     * le taux danois et suédois. Seules les bornes 0-100 sont opposables.
     */
    public function test_validation_accepte_un_taux_etranger_mais_refuse_l_absurde(): void
    {
        $valide = fn (int|float $rate) => [
            'date' => now()->format('Y-m-d'),
            'provider_name' => 'Test',
            'category' => Expense::CATEGORY_HARDWARE,
            'amount_ht' => 100,
            'vat_rate' => $rate,
        ];

        $this->actingAs($this->user)
            ->post(route('expenses.store'), $valide(25))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->user)
            ->post(route('expenses.store'), $valide(150))
            ->assertSessionHasErrors('vat_rate');

        $this->actingAs($this->user)
            ->post(route('expenses.store'), $valide(-5))
            ->assertSessionHasErrors('vat_rate');
    }

    /**
     * Le parcours réel de la demande : une facture Amazon.de qui n'affiche
     * qu'un total payé, saisie telle quelle.
     */
    public function test_une_depense_peut_etre_saisie_en_ttc(): void
    {
        $this->actingAs($this->user)
            ->post(route('expenses.store'), [
                'date' => now()->format('Y-m-d'),
                'provider_name' => 'Amazon.de',
                'supplier_country' => 'DE',
                'category' => Expense::CATEGORY_HARDWARE,
                'amount_input_mode' => 'ttc',
                'amount_ttc' => 119,
                'vat_rate' => 19,
                'vat_regime' => Expense::REGIME_FOREIGN_VAT,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('expenses.index'));

        $expense = Expense::latest('id')->first();

        $this->assertEquals('100.0000', $expense->amount_ht);
        $this->assertEquals('19.0000', $expense->amount_vat);
        $this->assertEquals('119.0000', $expense->amount_ttc);
        $this->assertFalse($expense->is_deductible, 'La TVA allemande ne se déduit pas ici.');
    }

    public function test_la_saisie_en_ttc_exige_un_montant_ttc(): void
    {
        $this->actingAs($this->user)
            ->post(route('expenses.store'), [
                'date' => now()->format('Y-m-d'),
                'provider_name' => 'Amazon.de',
                'category' => Expense::CATEGORY_HARDWARE,
                'amount_input_mode' => 'ttc',
                'vat_rate' => 19,
            ])
            ->assertSessionHasErrors('amount_ttc');
    }

    /**
     * L'API et les intégrations existantes n'envoient pas le mode de saisie.
     * Leur comportement ne doit pas changer d'un iota.
     */
    public function test_une_requete_sans_mode_de_saisie_reste_en_ht(): void
    {
        $this->actingAs($this->user)
            ->post(route('expenses.store'), [
                'date' => now()->format('Y-m-d'),
                'provider_name' => 'Fournisseur',
                'category' => Expense::CATEGORY_OFFICE,
                'amount_ht' => 100,
                'vat_rate' => 17,
            ])
            ->assertSessionHasNoErrors();

        $expense = Expense::latest('id')->first();

        $this->assertSame('ht', $expense->amount_input_mode);
        $this->assertSame('LU', $expense->supplier_country);
        $this->assertSame(Expense::REGIME_NATIONAL, $expense->vat_regime);
        $this->assertEquals('117.0000', $expense->amount_ttc);
    }

    public function test_le_formulaire_expose_les_pays_et_les_regimes(): void
    {
        $this->actingAs($this->user)
            ->get(route('expenses.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Expenses/Create')
                ->has('countries')
                ->has('vatRegimes')
                ->has('vatRatesByCountry.DE')
                ->has('vatRatesByCountry.FR')
            );
    }

    /**
     * Achat intracommunautaire saisi de bout en bout : facture hors taxe,
     * TVA autoliquidée au taux luxembourgeois, TTC inchangé.
     */
    public function test_une_acquisition_intracommunautaire_autoliquide_la_tva(): void
    {
        $this->actingAs($this->user)
            ->post(route('expenses.store'), [
                'date' => now()->format('Y-m-d'),
                'provider_name' => 'Fournisseur BE',
                'supplier_country' => 'BE',
                'category' => Expense::CATEGORY_HARDWARE,
                'amount_input_mode' => 'ht',
                'amount_ht' => 1000,
                'vat_rate' => 0,
                'vat_regime' => Expense::REGIME_REVERSE_CHARGE,
            ])
            ->assertSessionHasNoErrors();

        $expense = Expense::latest('id')->first();

        $this->assertEquals('0.0000', $expense->amount_vat, 'La facture du fournisseur ne porte pas de TVA.');
        $this->assertEquals('1000.0000', $expense->amount_ttc);
        $this->assertEquals('170.0000', $expense->reverse_charge_vat);
        $this->assertTrue($expense->is_deductible);
    }

    public function test_date_cannot_be_in_future(): void
    {
        $this->actingAs($this->user)
            ->post(route('expenses.store'), [
                'date' => now()->addDays(5)->format('Y-m-d'),
                'provider_name' => 'Test',
                'category' => Expense::CATEGORY_HARDWARE,
                'amount_ht' => 100,
                'vat_rate' => 17,
            ])
            ->assertSessionHasErrors('date');
    }

    public function test_summary_calculates_correct_totals(): void
    {
        $currentYear = now()->year;

        Expense::factory()->create([
            'date' => now(),
            'amount_ht' => 100,
            'vat_rate' => 17,
        ]);
        Expense::factory()->create([
            'date' => now(),
            'amount_ht' => 200,
            'vat_rate' => 17,
        ]);

        $this->actingAs($this->user)
            ->get(route('expenses.summary', ['year' => $currentYear]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('yearSummary.count', 2)
                ->has('yearSummary.total_ht')
                ->has('yearSummary.total_vat')
            );
    }
}
