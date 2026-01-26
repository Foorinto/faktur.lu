<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-3 months', '-1 hour');
        $durationSeconds = $this->faker->numberBetween(900, 28800); // 15 min to 8 hours
        $stoppedAt = (clone $startedAt)->modify("+{$durationSeconds} seconds");

        $projects = [
            'Site web vitrine',
            'Application mobile',
            'API REST',
            'Refonte graphique',
            'Migration serveur',
            'Maintenance',
            'Consulting',
            'Formation',
            'Support technique',
            'Développement WordPress',
            'E-commerce',
            'Dashboard analytics',
        ];

        $descriptions = [
            'Développement frontend',
            'Intégration maquette',
            'Configuration serveur',
            'Réunion client',
            'Code review',
            'Tests et débogage',
            'Documentation technique',
            'Optimisation performances',
            'Setup environnement',
            'Déploiement production',
        ];

        return [
            'client_id' => Client::factory(),
            'project_name' => $this->faker->randomElement($projects),
            'description' => $this->faker->optional(0.8)->randomElement($descriptions),
            'started_at' => $startedAt,
            'stopped_at' => $stoppedAt,
            'duration_seconds' => $durationSeconds,
            'hourly_rate' => $this->faker->randomElement([75, 85, 95, 100, 120, 150]),
            'is_billed' => false,
        ];
    }

    /**
     * Indicate that the time entry is billed.
     */
    public function billed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_billed' => true,
        ]);
    }

    /**
     * Indicate that the time entry is running (no stopped_at).
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => now()->subMinutes($this->faker->numberBetween(5, 120)),
            'stopped_at' => null,
            'duration_seconds' => 0,
        ]);
    }

    /**
     * Set a specific duration in minutes.
     */
    public function duration(int $minutes): static
    {
        return $this->state(function (array $attributes) use ($minutes) {
            $startedAt = $attributes['started_at'] ?? now()->subHours(2);
            $durationSeconds = $minutes * 60;

            return [
                'duration_seconds' => $durationSeconds,
                'stopped_at' => (clone $startedAt)->modify("+{$durationSeconds} seconds"),
            ];
        });
    }

    /**
     * Set time entry for today.
     */
    public function today(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = now()->setTime(
                $this->faker->numberBetween(8, 17),
                $this->faker->numberBetween(0, 59)
            );
            $durationSeconds = $this->faker->numberBetween(900, 14400);
            $stoppedAt = (clone $startedAt)->modify("+{$durationSeconds} seconds");

            // Make sure stopped_at doesn't go past now
            if ($stoppedAt > now()) {
                $stoppedAt = now();
                $durationSeconds = now()->diffInSeconds($startedAt);
            }

            return [
                'started_at' => $startedAt,
                'stopped_at' => $stoppedAt,
                'duration_seconds' => $durationSeconds,
            ];
        });
    }

    /**
     * Set time entry for this week.
     */
    public function thisWeek(): static
    {
        return $this->state(function (array $attributes) {
            $startOfWeek = now()->startOfWeek();
            $startedAt = $this->faker->dateTimeBetween($startOfWeek, 'now');
            $durationSeconds = $this->faker->numberBetween(900, 14400);
            $stoppedAt = (clone $startedAt)->modify("+{$durationSeconds} seconds");

            return [
                'started_at' => $startedAt,
                'stopped_at' => $stoppedAt,
                'duration_seconds' => $durationSeconds,
            ];
        });
    }

    /**
     * Set time entry for this month.
     */
    public function thisMonth(): static
    {
        return $this->state(function (array $attributes) {
            $startOfMonth = now()->startOfMonth();
            $startedAt = $this->faker->dateTimeBetween($startOfMonth, 'now');
            $durationSeconds = $this->faker->numberBetween(900, 14400);
            $stoppedAt = (clone $startedAt)->modify("+{$durationSeconds} seconds");

            return [
                'started_at' => $startedAt,
                'stopped_at' => $stoppedAt,
                'duration_seconds' => $durationSeconds,
            ];
        });
    }

    /**
     * Set specific project name.
     */
    public function project(string $projectName): static
    {
        return $this->state(fn (array $attributes) => [
            'project_name' => $projectName,
        ]);
    }

    /**
     * Set specific client.
     */
    public function forClient(Client $client): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->id,
            'hourly_rate' => $client->default_hourly_rate ?? $attributes['hourly_rate'],
        ]);
    }
}
