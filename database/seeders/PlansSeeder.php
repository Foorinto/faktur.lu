<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete old starter plan if exists
        Plan::where('name', 'starter')->delete();

        // Plan Essentiel (4€/mois)
        Plan::updateOrCreate(
            ['name' => 'essentiel'],
            [
                'display_name' => 'Essentiel',
                'description' => 'Pour les freelances débutants',
                'price_monthly' => 400, // 4€ in cents
                'price_yearly' => 4000, // 40€ in cents (2 mois offerts)
                'stripe_price_id_monthly' => env('STRIPE_PRICE_ESSENTIEL_MONTHLY'),
                'stripe_price_id_yearly' => env('STRIPE_PRICE_ESSENTIEL_YEARLY'),
                'limits' => [
                    'max_clients' => 10,
                    'max_invoices_per_month' => 20,
                    'max_quotes_per_month' => 20,
                    'max_emails_per_month' => 30,
                ],
                'features' => [
                    'invoices',
                    'quotes',
                    'clients',
                    'expenses',
                    'time_tracking',
                    '2fa',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        // Plan Pro (9€/mois)
        Plan::updateOrCreate(
            ['name' => 'pro'],
            [
                'display_name' => 'Pro',
                'description' => 'Pour les freelances établis',
                'price_monthly' => 900, // 9€ in cents
                'price_yearly' => 9000, // 90€ in cents (2 mois offerts)
                'stripe_price_id_monthly' => env('STRIPE_PRICE_PRO_MONTHLY'),
                'stripe_price_id_yearly' => env('STRIPE_PRICE_PRO_YEARLY'),
                'limits' => [
                    'max_clients' => null, // unlimited
                    'max_invoices_per_month' => null, // unlimited
                    'max_quotes_per_month' => null, // unlimited
                    'max_emails_per_month' => null, // unlimited
                ],
                'features' => [
                    'invoices',
                    'quotes',
                    'clients',
                    'expenses',
                    'time_tracking',
                    '2fa',
                    'faia_export',
                    'pdf_archive',
                    'email_reminders',
                    'no_branding',
                    'priority_support',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ]
        );
    }
}
