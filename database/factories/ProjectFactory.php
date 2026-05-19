<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'status' => 'backlog',
            'is_archived' => false,
        ];
    }

    public function archived(): self
    {
        return $this->state(fn () => ['is_archived' => true]);
    }
}
