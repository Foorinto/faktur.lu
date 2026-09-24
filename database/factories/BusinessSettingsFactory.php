<?php

namespace Database\Factories;

use App\Models\BusinessSettings;
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
            // Mentions légales complètes par défaut (FEAT-133) : une profession
            // libérale sans autorisation n'a rien d'autre à fournir. Les tests
            // qui veulent un compte incomplet le disent explicitement.
            'exercise_form' => BusinessSettings::EXERCISE_FORM_LIBERAL,
            'no_establishment_authorization' => true,
            'iban' => 'LU' . fake()->numerify('## #### #### #### ####'),
            'bic' => 'BGLL' . fake()->randomLetter() . fake()->randomLetter() . 'LL',
            // Défaut DÉTERMINISTE, volontairement.
            //
            // Ce champ était tiré au hasard entre 'assujetti' et 'franchise', ce
            // qui rendait intermittent tout test touchant la TVA : une facture à
            // 17 % passait ou échouait selon le tirage. Les cas franchise
            // disposent de l'état ->franchise() ci-dessous, à utiliser
            // explicitement.
            'vat_regime' => 'assujetti',
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
