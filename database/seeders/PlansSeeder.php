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
        // Plan Starter (Gratuit)
        Plan::updateOrCreate(
            ['name' => 'starter'],
            [
                'display_name' => 'Starter',
                'description' => 'Pour démarrer',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly' => null,
                'limits' => [
                    'max_clients' => 2,
                    'max_invoices_per_month' => 2,
                    'max_quotes_per_month' => 2,
                    'max_emails_per_month' => 2,
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

        // Plan Pro (7€/mois)
        Plan::updateOrCreate(
            ['name' => 'pro'],
            [
                'display_name' => 'Pro',
                'description' => 'Pour les indépendants',
                'price_monthly' => 700, // 7€ in cents
                'price_yearly' => 7000, // 70€ in cents (2 mois offerts)
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
