<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\TimeEntry;
use Illuminate\Database\Seeder;

class TimeEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::all();

        if ($clients->isEmpty()) {
            $this->command->warn('No clients found. Creating sample clients first.');
            $clients = Client::factory()->count(3)->create();
        }

        $projects = [
            'Site web vitrine',
            'Application mobile',
            'API REST',
            'Maintenance',
        ];

        foreach ($clients as $client) {
            // Create some unbilled entries for each client
            foreach ($projects as $project) {
                // Random number of entries per project (1-5)
                $count = rand(1, 5);
                TimeEntry::factory()
                    ->count($count)
                    ->forClient($client)
                    ->project($project)
                    ->create();
            }

            // Create some billed entries
            TimeEntry::factory()
                ->count(rand(3, 8))
                ->forClient($client)
                ->billed()
                ->create();
        }

        // Create some recent entries for demo purposes
        $recentClient = $clients->first();

        // Today's entries
        TimeEntry::factory()
            ->count(2)
            ->forClient($recentClient)
            ->today()
            ->create();

        // This week's entries
        TimeEntry::factory()
            ->count(5)
            ->forClient($recentClient)
            ->thisWeek()
            ->create();

        $this->command->info('Time entries seeded successfully.');
    }
}
