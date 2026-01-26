<?php

namespace Database\Seeders;

use App\Models\BusinessSettings;
use Illuminate\Database\Seeder;

class BusinessSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only create if not exists (Singleton pattern)
        if (BusinessSettings::exists()) {
            return;
        }

        BusinessSettings::create([
            'company_name' => 'LuxDev Consulting',
            'legal_name' => 'Alexandre Beaudier',
            'address' => '12 Rue de la Gare',
            'postal_code' => 'L-1234',
            'city' => 'Luxembourg',
            'country_code' => 'LU',
            'vat_number' => null,
            'matricule' => '12345678901',
            'iban' => 'LU123456789012345678',
            'bic' => 'BGLLLULL',
            'vat_regime' => 'franchise',
            'phone' => '+352 123 456 789',
            'email' => 'contact@luxdev.lu',
        ]);
    }
}
