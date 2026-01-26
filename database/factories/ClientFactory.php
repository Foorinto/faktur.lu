<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['b2b', 'b2c']);
        $countryCode = fake()->randomElement(['LU', 'BE', 'FR', 'DE']);

        return [
            'name' => $type === 'b2b' ? fake()->company() : fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'address' => fake()->streetAddress(),
            'postal_code' => $this->getPostalCode($countryCode),
            'city' => fake()->city(),
            'country_code' => $countryCode,
            'vat_number' => $type === 'b2b' ? $this->getVatNumber($countryCode) : null,
            'type' => $type,
            'currency' => 'EUR',
            'phone' => fake()->phoneNumber(),
            'notes' => fake()->optional(0.3)->sentence(),
            'default_hourly_rate' => fake()->optional(0.7)->randomFloat(2, 50, 200),
        ];
    }

    /**
     * Configure the client as a business (B2B).
     */
    public function b2b(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->company(),
            'type' => 'b2b',
            'vat_number' => $this->getVatNumber($attributes['country_code'] ?? 'LU'),
        ]);
    }

    /**
     * Configure the client as an individual (B2C).
     */
    public function b2c(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->name(),
            'type' => 'b2c',
            'vat_number' => null,
        ]);
    }

    /**
     * Configure the client from Luxembourg.
     */
    public function luxembourg(): static
    {
        return $this->state(fn (array $attributes) => [
            'country_code' => 'LU',
            'postal_code' => 'L-' . fake()->numberBetween(1000, 9999),
            'vat_number' => $attributes['type'] === 'b2b' ? 'LU' . fake()->numerify('########') : null,
        ]);
    }

    /**
     * Configure the client from France.
     */
    public function france(): static
    {
        return $this->state(fn (array $attributes) => [
            'country_code' => 'FR',
            'postal_code' => fake()->numerify('#####'),
            'vat_number' => $attributes['type'] === 'b2b' ? 'FR' . fake()->numerify('###########') : null,
        ]);
    }

    /**
     * Get a postal code based on country.
     */
    private function getPostalCode(string $countryCode): string
    {
        return match ($countryCode) {
            'LU' => 'L-' . fake()->numberBetween(1000, 9999),
            'BE' => fake()->numerify('####'),
            'FR' => fake()->numerify('#####'),
            'DE' => fake()->numerify('#####'),
            default => fake()->postcode(),
        };
    }

    /**
     * Get a VAT number based on country.
     */
    private function getVatNumber(string $countryCode): string
    {
        return match ($countryCode) {
            'LU' => 'LU' . fake()->numerify('########'),
            'BE' => 'BE' . fake()->numerify('##########'),
            'FR' => 'FR' . fake()->numerify('###########'),
            'DE' => 'DE' . fake()->numerify('#########'),
            default => $countryCode . fake()->numerify('########'),
        };
    }
}
