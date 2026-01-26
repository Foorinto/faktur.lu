<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BusinessSettings>
 */
class BusinessSettingsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => fake()->company(),
            'legal_name' => fake()->name(),
            'address' => fake()->streetAddress(),
            'postal_code' => 'L-' . fake()->numberBetween(1000, 9999),
            'city' => fake()->city(),
            'country_code' => 'LU',
            'vat_number' => 'LU' . fake()->numerify('########'),
            'matricule' => fake()->numerify('###########'),
            'iban' => 'LU' . fake()->numerify('## #### #### #### ####'),
            'bic' => 'BGLL' . fake()->randomLetter() . fake()->randomLetter() . 'LL',
            'vat_regime' => fake()->randomElement(['assujetti', 'franchise']),
            'phone' => '+352 ' . fake()->numerify('### ### ###'),
            'email' => fake()->companyEmail(),
        ];
    }

    /**
     * Configure the model as VAT exempt (franchise).
     */
    public function franchise(): static
    {
        return $this->state(fn (array $attributes) => [
            'vat_regime' => 'franchise',
            'vat_number' => null,
        ]);
    }

    /**
     * Configure the model as VAT registered (assujetti).
     */
    public function assujetti(): static
    {
        return $this->state(fn (array $attributes) => [
            'vat_regime' => 'assujetti',
            'vat_number' => 'LU' . fake()->numerify('########'),
        ]);
    }
}
