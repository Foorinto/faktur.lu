<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'designation' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'reference' => $this->faker->optional()->bothify('REF-####'),
            'unit_price_ht' => $this->faker->randomFloat(2, 5, 500),
            'vat_rate' => 17,
            'unit' => 'piece',
            'is_active' => true,
        ];
    }
}
