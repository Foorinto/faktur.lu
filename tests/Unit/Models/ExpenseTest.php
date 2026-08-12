<?php

namespace Tests\Unit\Models;

use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_calculates_vat_and_ttc_on_creation(): void
    {
        $expense = Expense::create([
            'date' => now(),
            'provider_name' => 'Test Provider',
            'category' => Expense::CATEGORY_HARDWARE,
            'amount_ht' => 100,
            'vat_rate' => 17,
        ]);

        $this->assertEquals('17.0000', $expense->amount_vat);
        $this->assertEquals('117.0000', $expense->amount_ttc);
    }

    public function test_expense_recalculates_on_update(): void
    {
        $expense = Expense::factory()->create([
            'amount_ht' => 100,
            'vat_rate' => 17,
        ]);

        $expense->update(['amount_ht' => 200]);

        $this->assertEquals('34.0000', $expense->amount_vat);
        $this->assertEquals('234.0000', $expense->amount_ttc);
    }

    public function test_expense_handles_zero_vat(): void
    {
        $expense = Expense::create([
            'date' => now(),
            'provider_name' => 'Test Provider',
            'category' => Expense::CATEGORY_SOFTWARE,
            'amount_ht' => 100,
            'vat_rate' => 0,
        ]);

        $this->assertEquals('0.0000', $expense->amount_vat);
        $this->assertEquals('100.0000', $expense->amount_ttc);
    }

    public function test_expense_handles_different_vat_rates(): void
    {
        $rates = [
            3 => ['vat' => '3.0000', 'ttc' => '103.0000'],
            8 => ['vat' => '8.0000', 'ttc' => '108.0000'],
            14 => ['vat' => '14.0000', 'ttc' => '114.0000'],
            17 => ['vat' => '17.0000', 'ttc' => '117.0000'],
        ];

        foreach ($rates as $rate => $expected) {
            $expense = Expense::create([
                'date' => now(),
                'provider_name' => 'Test',
                'category' => Expense::CATEGORY_OTHER,
                'amount_ht' => 100,
                'vat_rate' => $rate,
            ]);

            $this->assertEquals($expected['vat'], $expense->amount_vat, "VAT for rate $rate%");
            $this->assertEquals($expected['ttc'], $expense->amount_ttc, "TTC for rate $rate%");
        }
    }

    public function test_get_categories_returns_all_categories(): void
    {
        $categories = Expense::getCategories();

        $this->assertArrayHasKey(Expense::CATEGORY_HARDWARE, $categories);
        $this->assertArrayHasKey(Expense::CATEGORY_SOFTWARE, $categories);
        $this->assertArrayHasKey(Expense::CATEGORY_HOSTING, $categories);
        $this->assertArrayHasKey(Expense::CATEGORY_OFFICE, $categories);
        $this->assertArrayHasKey(Expense::CATEGORY_TRAVEL, $categories);
        $this->assertArrayHasKey(Expense::CATEGORY_TRAINING, $categories);
        $this->assertArrayHasKey(Expense::CATEGORY_PROFESSIONAL_SERVICES, $categories);
        $this->assertArrayHasKey(Expense::CATEGORY_TELECOMMUNICATIONS, $categories);
        $this->assertArrayHasKey(Expense::CATEGORY_OTHER, $categories);
    }

    public function test_category_label_attribute(): void
    {
        $expense = Expense::factory()->create(['category' => Expense::CATEGORY_HARDWARE]);

        $this->assertEquals('Matériel informatique', $expense->category_label);
    }

    public function test_scope_category_filters_correctly(): void
    {
        Expense::factory()->create(['category' => Expense::CATEGORY_HARDWARE]);
        Expense::factory()->create(['category' => Expense::CATEGORY_SOFTWARE]);
        Expense::factory()->create(['category' => Expense::CATEGORY_HARDWARE]);

        $hardware = Expense::category(Expense::CATEGORY_HARDWARE)->get();

        $this->assertCount(2, $hardware);
    }

    public function test_scope_for_year_filters_correctly(): void
    {
        Expense::factory()->create(['date' => '2024-06-15']);
        Expense::factory()->create(['date' => '2024-12-01']);
        Expense::factory()->create(['date' => '2023-06-15']);

        $expenses2024 = Expense::forYear(2024)->get();

        $this->assertCount(2, $expenses2024);
    }

    public function test_scope_for_month_filters_correctly(): void
    {
        Expense::factory()->create(['date' => '2024-06-15']);
        Expense::factory()->create(['date' => '2024-06-20']);
        Expense::factory()->create(['date' => '2024-07-15']);

        $juneExpenses = Expense::forMonth(2024, 6)->get();

        $this->assertCount(2, $juneExpenses);
    }

    public function test_scope_deductible_filters_correctly(): void
    {
        Expense::factory()->create(['is_deductible' => true]);
        Expense::factory()->create(['is_deductible' => true]);
        Expense::factory()->create(['is_deductible' => false]);

        $deductible = Expense::deductible()->get();

        $this->assertCount(2, $deductible);
    }

    public function test_get_summary_returns_correct_totals(): void
    {
        Expense::factory()->create([
            'date' => '2024-06-15',
            'amount_ht' => 100,
            'vat_rate' => 17,
        ]);
        Expense::factory()->create([
            'date' => '2024-06-20',
            'amount_ht' => 200,
            'vat_rate' => 17,
        ]);

        $summary = Expense::getSummary(2024, 6);

        $this->assertEquals(2, $summary['count']);
        $this->assertEquals('300.0000', $summary['total_ht']);
        $this->assertEquals('51.0000', $summary['total_vat']);
        $this->assertEquals('351.0000', $summary['total_ttc']);
    }

    public function test_soft_deletes(): void
    {
        $expense = Expense::factory()->create();

        $expense->delete();

        $this->assertSoftDeleted($expense);
        $this->assertNotNull(Expense::withTrashed()->find($expense->id));
    }

    public function test_supplier_countries_are_listed_alphabetically(): void
    {
        $countries = Expense::getSupplierCountries();
        $names = array_column($countries, 'name');

        // « Hors UE » ferme la liste et ne participe pas au tri.
        $eu = array_slice($names, 0, -1);
        $trie = $eu;
        sort($trie, SORT_LOCALE_STRING);

        $this->assertSame('Allemagne', $eu[0], 'Le tri se fait sur le nom, pas sur le code ISO.');
        $this->assertSame('Autriche', $eu[1]);
        $this->assertSame(
            Expense::COUNTRY_NON_EU,
            end($countries)['code'],
            'Le hors-UE reste en dernier.'
        );
    }

    public function test_ttc_input_derives_the_net_amount(): void
    {
        $expense = Expense::create([
            'date' => now(),
            'provider_name' => 'Amazon.de',
            'category' => Expense::CATEGORY_HARDWARE,
            'amount_input_mode' => Expense::INPUT_TTC,
            'amount_ttc' => 119,
            'vat_rate' => 19,
        ]);

        $this->assertEquals('100.0000', $expense->amount_ht);
        $this->assertEquals('19.0000', $expense->amount_vat);
        $this->assertEquals('119.0000', $expense->amount_ttc);
    }

    /**
     * Le TTC est le montant réellement débité : il doit rester intact, même
     * quand la division ne tombe pas juste. C'est la TVA qui absorbe la
     * fraction d'arrondi, jamais le total.
     */
    public function test_ttc_input_keeps_the_paid_amount_exact(): void
    {
        $expense = Expense::create([
            'date' => now(),
            'provider_name' => 'Fournisseur',
            'category' => Expense::CATEGORY_OFFICE,
            'amount_input_mode' => Expense::INPUT_TTC,
            'amount_ttc' => 100,
            'vat_rate' => 17,
        ]);

        $this->assertEquals('100.0000', $expense->amount_ttc);
        $this->assertEquals(
            '100.0000',
            bcadd($expense->amount_ht, $expense->amount_vat, 4),
            'HT + TVA doit retomber exactement sur le montant payé.'
        );
    }

    public function test_ht_remains_the_default_input_mode(): void
    {
        $expense = Expense::create([
            'date' => now(),
            'provider_name' => 'Fournisseur',
            'category' => Expense::CATEGORY_OFFICE,
            'amount_ht' => 100,
            'vat_rate' => 17,
        ]);

        $this->assertSame(Expense::INPUT_HT, $expense->amount_input_mode);
        $this->assertEquals('117.0000', $expense->amount_ttc);
    }

    public function test_foreign_vat_is_never_deductible(): void
    {
        $expense = Expense::create([
            'date' => now(),
            'provider_name' => 'Amazon.fr',
            'supplier_country' => 'FR',
            'category' => Expense::CATEGORY_HARDWARE,
            'amount_ht' => 100,
            'vat_rate' => 20,
            'vat_regime' => Expense::REGIME_FOREIGN_VAT,
            // Explicitement demandée déductible : le régime doit primer.
            'is_deductible' => true,
        ]);

        $this->assertFalse($expense->is_deductible);
    }

    public function test_foreign_vat_stays_out_of_the_deductible_total(): void
    {
        Expense::create([
            'date' => now(),
            'provider_name' => 'Fournisseur LU',
            'category' => Expense::CATEGORY_OFFICE,
            'amount_ht' => 100,
            'vat_rate' => 17,
        ]);

        Expense::create([
            'date' => now(),
            'provider_name' => 'Amazon.de',
            'supplier_country' => 'DE',
            'category' => Expense::CATEGORY_HARDWARE,
            'amount_ht' => 100,
            'vat_rate' => 19,
            'vat_regime' => Expense::REGIME_FOREIGN_VAT,
        ]);

        $this->assertEquals('17.0000', Expense::deductible()->sum('amount_vat'));
    }

    public function test_reverse_charge_clears_any_residual_rate(): void
    {
        $expense = Expense::create([
            'date' => now(),
            'provider_name' => 'Fournisseur BE',
            'supplier_country' => 'BE',
            'category' => Expense::CATEGORY_SOFTWARE,
            'amount_ht' => 100,
            'vat_rate' => 21,
            'vat_regime' => Expense::REGIME_REVERSE_CHARGE,
        ]);

        $this->assertEquals('0.00', $expense->vat_rate);
        $this->assertEquals('0.0000', $expense->amount_vat);
        $this->assertEquals('100.0000', $expense->amount_ttc);
    }

    /**
     * La TVA autoliquidée se calcule au taux du pays de l'acheteur, sur la
     * base hors taxe, et reste hors du TTC : le fournisseur n'a facturé que le
     * hors taxe, rien de plus n'a été débité.
     */
    public function test_reverse_charge_self_assesses_at_the_home_rate(): void
    {
        $expense = Expense::create([
            'date' => now(),
            'provider_name' => 'Fournisseur BE',
            'supplier_country' => 'BE',
            'category' => Expense::CATEGORY_HARDWARE,
            'amount_ht' => 1000,
            'vat_rate' => 21,
            'vat_regime' => Expense::REGIME_REVERSE_CHARGE,
        ]);

        $this->assertEquals('17.00', $expense->reverse_charge_vat_rate, 'Le taux luxembourgeois, pas le belge.');
        $this->assertEquals('170.0000', $expense->reverse_charge_vat);
        $this->assertEquals('1000.0000', $expense->amount_ttc, 'Le TTC ne bouge pas : rien de plus n\'a été payé.');
    }

    public function test_a_chosen_reverse_charge_rate_is_kept(): void
    {
        $expense = Expense::create([
            'date' => now(),
            'provider_name' => 'Fournisseur NL',
            'supplier_country' => 'NL',
            'category' => Expense::CATEGORY_OTHER,
            'amount_ht' => 1000,
            'vat_regime' => Expense::REGIME_REVERSE_CHARGE,
            'reverse_charge_vat_rate' => 3,
            'vat_rate' => 0,
        ]);

        $this->assertEquals('30.0000', $expense->reverse_charge_vat);
    }

    /**
     * Changer de régime doit effacer la TVA autoliquidée. La laisser derrière
     * elle la ferait remonter indéfiniment dans la déclaration, sur une
     * dépense qui n'est plus une acquisition intracommunautaire.
     */
    public function test_leaving_reverse_charge_clears_the_self_assessed_vat(): void
    {
        $expense = Expense::create([
            'date' => now(),
            'provider_name' => 'Fournisseur BE',
            'supplier_country' => 'BE',
            'category' => Expense::CATEGORY_HARDWARE,
            'amount_ht' => 1000,
            'vat_regime' => Expense::REGIME_REVERSE_CHARGE,
            'vat_rate' => 0,
        ]);

        $this->assertEquals('170.0000', $expense->reverse_charge_vat);

        $expense->update([
            'vat_regime' => Expense::REGIME_NATIONAL,
            'vat_rate' => 17,
        ]);

        $this->assertEquals('0.0000', $expense->reverse_charge_vat);
        $this->assertNull($expense->reverse_charge_vat_rate);
        $this->assertEquals('170.0000', $expense->amount_vat, 'La TVA redevient celle de la facture.');
    }

    public function test_other_regimes_never_carry_self_assessed_vat(): void
    {
        $expense = Expense::create([
            'date' => now(),
            'provider_name' => 'Amazon.de',
            'supplier_country' => 'DE',
            'category' => Expense::CATEGORY_HARDWARE,
            'amount_ht' => 1000,
            'vat_rate' => 19,
            'vat_regime' => Expense::REGIME_FOREIGN_VAT,
            // Envoyé malgré tout : le régime doit primer.
            'reverse_charge_vat_rate' => 17,
        ]);

        $this->assertEquals('0.0000', $expense->reverse_charge_vat);
        $this->assertNull($expense->reverse_charge_vat_rate);
    }
}
