<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amountHt = $this->faker->randomFloat(2, 10, 1000);
        $vatRate = $this->faker->randomElement([0, 3, 8, 14, 17]);
        $amountVat = bcmul((string) $amountHt, bcdiv((string) $vatRate, '100', 4), 4);
        $amountTtc = bcadd((string) $amountHt, $amountVat, 4);

        $providers = [
            'Amazon', 'JetBrains', 'OVH', 'Scaleway', 'GitHub',
            'Microsoft', 'Apple', 'Google Cloud', 'Adobe',
            'Zoom', 'Slack', 'Notion', 'Figma', 'Vercel',
            'DigitalOcean', 'AWS', 'Office Depot', 'IKEA',
            'SNCF', 'Air France', 'Hilton', 'Expert-comptable SA',
        ];

        return [
            'date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'provider_name' => $this->faker->randomElement($providers),
            'category' => $this->faker->randomElement(array_keys(Expense::getCategories())),
            'amount_ht' => $amountHt,
            'vat_rate' => $vatRate,
            'amount_vat' => $amountVat,
            'amount_ttc' => $amountTtc,
            'description' => $this->faker->optional(0.7)->sentence(),
            'is_deductible' => $this->faker->boolean(90),
            'payment_method' => $this->faker->randomElement(array_keys(Expense::getPaymentMethods())),
            'reference' => $this->faker->optional(0.5)->bothify('INV-####-????'),
        ];
    }

    /**
     * En production, toute dépense porte au moins une ligne de ventilation
     * (migration de reprise + contrôleur). Les tests doivent refléter ce même
     * état : sans quoi ils exerceraient un chemin qui n'existe plus en prod.
     * La ligne recopie la dépense — pour une dépense mono-catégorie, l'agrégat
     * reste identique, ce qui préserve la non-régression.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Expense $expense) {
            // Sans propriétaire (tests de calcul pur, hors authentification), on
            // ne peut pas rattacher une ligne isolée : ces dépenses n'entrent ni
            // dans le récapitulatif fiscal ni dans les exports, la ligne serait
            // inutile. Le modèle porte la règle, ici comme à la génération des
            // charges fixes.
            $expense->ensureDerivedLine();
        });
    }

    /**
     * Indicate that the expense is for hardware.
     */
    public function hardware(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => Expense::CATEGORY_HARDWARE,
            'provider_name' => $this->faker->randomElement(['Amazon', 'Apple', 'Dell', 'Lenovo', 'LDLC']),
        ]);
    }

    /**
     * Indicate that the expense is for software.
     */
    public function software(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => Expense::CATEGORY_SOFTWARE,
            'provider_name' => $this->faker->randomElement(['JetBrains', 'Microsoft', 'Adobe', 'GitHub', 'Notion']),
        ]);
    }

    /**
     * Indicate that the expense is for hosting.
     */
    public function hosting(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => Expense::CATEGORY_HOSTING,
            'provider_name' => $this->faker->randomElement(['OVH', 'Scaleway', 'DigitalOcean', 'AWS', 'Vercel']),
        ]);
    }

    /**
     * Indicate that the expense is not deductible.
     */
    public function nonDeductible(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_deductible' => false,
        ]);
    }

    /**
     * Set expense for a specific month.
     */
    public function forMonth(int $year, int $month): static
    {
        $startDate = now()->setYear($year)->setMonth($month)->startOfMonth();
        $endDate = now()->setYear($year)->setMonth($month)->endOfMonth();

        return $this->state(fn (array $attributes) => [
            'date' => $this->faker->dateTimeBetween($startDate, $endDate),
        ]);
    }
}
