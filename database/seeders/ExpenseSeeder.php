<?php

namespace Database\Seeders;

use App\Models\Expense;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = now()->year;

        // Create expenses for the current year
        for ($month = 1; $month <= now()->month; $month++) {
            // Random number of expenses per month (3-8)
            $count = rand(3, 8);

            Expense::factory()
                ->count($count)
                ->forMonth($currentYear, $month)
                ->create();
        }

        // Add some specific expenses
        Expense::factory()->hardware()->create([
            'date' => now()->subDays(10),
            'provider_name' => 'Apple',
            'amount_ht' => 1499,
            'vat_rate' => 17,
            'description' => 'MacBook Pro 14"',
        ]);

        Expense::factory()->software()->create([
            'date' => now()->subDays(5),
            'provider_name' => 'JetBrains',
            'amount_ht' => 249,
            'vat_rate' => 17,
            'description' => 'Licence annuelle PhpStorm',
        ]);

        Expense::factory()->hosting()->create([
            'date' => now()->subDays(2),
            'provider_name' => 'OVH',
            'amount_ht' => 89,
            'vat_rate' => 17,
            'description' => 'Serveur VPS mensuel',
        ]);

        $this->command->info('Expenses seeded successfully.');
    }
}
