<?php

namespace Database\Factories\HR;

use App\Models\HR\HrEventParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

class HrEventParticipantFactory extends Factory
{
    protected $model = HrEventParticipant::class;

    public function definition(): array
    {
        return [
            'hr_event_id' => null,
            'employee_id' => null,
            'external_email' => $this->faker->safeEmail(),
            'response' => 'pending',
        ];
    }

    public function accepted(): self
    {
        return $this->state(fn () => ['response' => 'accepted']);
    }

    public function declined(): self
    {
        return $this->state(fn () => ['response' => 'declined']);
    }
}
