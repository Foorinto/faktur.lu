<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\RecurringExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringExpense>
 */
class RecurringExpenseFactory extends Factory
{
    protected $model = RecurringExpense::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => $this->faker->randomElement(['Loyer du bureau', 'Abonnement téléphonique', 'Assurance responsabilité civile', 'Hébergement']),
            'frequency' => RecurringExpense::FREQUENCY_MONTHLY,
            'next_expense_date' => now()->toDateString(),
            'ends_at' => null,
            'is_active' => true,
            'provider_name' => $this->faker->randomElement(['Immo Lux Sàrl', 'POST Luxembourg', 'Foyer Assurances', 'OVH']),
            'supplier_country' => 'LU',
            'category' => Expense::CATEGORY_OTHER,
            'amount_input_mode' => Expense::INPUT_HT,
            'amount' => $this->faker->randomFloat(2, 20, 2000),
            'vat_rate' => 17,
            'vat_regime' => Expense::REGIME_NATIONAL,
            'is_deductible' => true,
            'payment_method' => Expense::PAYMENT_TRANSFER,
            'description' => null,
        ];
    }

    /** Une charge mise en pause : elle ne doit rien générer. */
    public function suspendue(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /** Une charge dont l'échéance est déjà passée. */
    public function due(?string $date = null): static
    {
        return $this->state(fn () => ['next_expense_date' => $date ?? now()->subDay()->toDateString()]);
    }
}
