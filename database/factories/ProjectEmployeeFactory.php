<?php

namespace Database\Factories;

use App\Models\HR\Employee;
use App\Models\Project;
use App\Models\ProjectEmployee;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectEmployeeFactory extends Factory
{
    protected $model = ProjectEmployee::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'employee_id' => Employee::factory(),
            'active' => true,
            'added_at' => now(),
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn () => ['active' => false]);
    }
}
