<?php

namespace Database\Factories\HR;

use App\Models\HR\Employee;
use App\Models\HR\HrEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HrEventFactory extends Factory
{
    protected $model = HrEvent::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('+1 day', '+1 month');
        $end = (clone $start)->modify('+1 hour');

        return [
            'user_id' => User::factory(),
            'created_by_employee_id' => Employee::factory(),
            'title' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement(HrEvent::TYPES),
            'color' => null,
            'starts_at' => $start,
            'ends_at' => $end,
            'is_all_day' => false,
            'location_type' => 'none',
            'room_id' => null,
            'address' => null,
            'video_url' => null,
            'description' => $this->faker->optional()->paragraph(),
            'recurrence_rule' => null,
        ];
    }

    public function meeting(): self
    {
        return $this->state(fn () => ['type' => 'meeting']);
    }

    public function allDay(): self
    {
        return $this->state(fn () => ['is_all_day' => true]);
    }

    public function inRoom(int $roomId): self
    {
        return $this->state(fn () => [
            'location_type' => 'room',
            'room_id' => $roomId,
        ]);
    }
}
