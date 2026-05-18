<?php

namespace Database\Factories\HR;

use App\Models\HR\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->randomElement(['Salle Belair', 'Salle Kirchberg', 'Bureau 1.2', 'Open space']),
            'capacity' => $this->faker->numberBetween(2, 20),
            'location' => $this->faker->randomElement(['Rez-de-chaussée', '1er étage', '2e étage']),
            'equipment' => $this->faker->randomElements(['projector', 'whiteboard', 'video_conf', 'tv_screen'], 2),
            'active' => true,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn () => ['active' => false]);
    }
}
