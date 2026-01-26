<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some specific example clients
        Client::create([
            'name' => 'ACME Corporation',
            'email' => 'contact@acme.lu',
            'address' => '45 Avenue Kennedy',
            'postal_code' => 'L-2000',
            'city' => 'Luxembourg',
            'country_code' => 'LU',
            'vat_number' => 'LU12345678',
            'type' => 'b2b',
            'currency' => 'EUR',
            'phone' => '+352 26 12 34 56',
            'notes' => 'Client principal - contrat annuel de maintenance',
        ]);

        Client::create([
            'name' => 'Tech Solutions SàRL',
            'email' => 'info@techsolutions.lu',
            'address' => '12 Rue du Commerce',
            'postal_code' => 'L-1234',
            'city' => 'Esch-sur-Alzette',
            'country_code' => 'LU',
            'vat_number' => 'LU87654321',
            'type' => 'b2b',
            'currency' => 'EUR',
            'phone' => '+352 27 98 76 54',
        ]);

        Client::create([
            'name' => 'Jean Dupont',
            'email' => 'jean.dupont@gmail.com',
            'address' => '8 Rue de la Liberté',
            'postal_code' => 'L-3850',
            'city' => 'Schifflange',
            'country_code' => 'LU',
            'type' => 'b2c',
            'currency' => 'EUR',
            'phone' => '+352 621 123 456',
        ]);

        Client::create([
            'name' => 'Société Générale de Belgique',
            'email' => 'finance@sgb.be',
            'address' => '100 Avenue Louise',
            'postal_code' => '1050',
            'city' => 'Bruxelles',
            'country_code' => 'BE',
            'vat_number' => 'BE0123456789',
            'type' => 'b2b',
            'currency' => 'EUR',
        ]);

        // Create additional random clients
        Client::factory()->count(6)->create();
    }
}
