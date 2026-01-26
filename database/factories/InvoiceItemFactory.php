<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 10);
        $unitPrice = $this->faker->randomFloat(2, 50, 500);
        $vatRate = $this->faker->randomElement([3, 8, 14, 17]);
        $totalHt = bcmul((string) $quantity, (string) $unitPrice, 4);
        $totalVat = bcmul($totalHt, bcdiv((string) $vatRate, '100', 4), 4);
        $totalTtc = bcadd($totalHt, $totalVat, 4);

        return [
            'invoice_id' => Invoice::factory(),
            'title' => $this->faker->randomElement([
                'Développement API REST',
                'Intégration front-end Vue.js',
                'Configuration serveur',
                'Maintenance mensuelle',
                'Consultation technique',
                'Formation équipe',
                'Audit de sécurité',
                'Optimisation base de données',
                'Développement module e-commerce',
                'Support technique',
            ]),
            'description' => $this->faker->optional(0.5)->sentence(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'vat_rate' => $vatRate,
            'total_ht' => $totalHt,
            'total_vat' => $totalVat,
            'total_ttc' => $totalTtc,
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }

    /**
     * Set specific VAT rate.
     */
    public function vatRate(int $rate): static
    {
        return $this->state(function (array $attributes) use ($rate) {
            $totalHt = bcmul((string) $attributes['quantity'], (string) $attributes['unit_price'], 4);
            $totalVat = bcmul($totalHt, bcdiv((string) $rate, '100', 4), 4);
            $totalTtc = bcadd($totalHt, $totalVat, 4);

            return [
                'vat_rate' => $rate,
                'total_ht' => $totalHt,
                'total_vat' => $totalVat,
                'total_ttc' => $totalTtc,
            ];
        });
    }

    /**
     * Create item with no VAT (franchise de TVA).
     */
    public function noVat(): static
    {
        return $this->vatRate(0);
    }

    /**
     * Create item with standard VAT (17%).
     */
    public function standardVat(): static
    {
        return $this->vatRate(17);
    }
}
