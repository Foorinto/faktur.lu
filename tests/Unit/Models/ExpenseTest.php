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
}
