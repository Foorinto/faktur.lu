<?php

namespace Database\Factories\HR;

use App\Models\HR\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email_pro' => $this->faker->safeEmail(),
            'contract_type' => 'CDI',
            'contract_start' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'job_title' => $this->faker->jobTitle(),
            'status' => 'active',
            'hide_leaves_from_team' => false,
        ];
    }

    public function active(): self
    {
        return $this->state(fn () => ['status' => 'active']);
    }
}
