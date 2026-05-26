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

        // Plan Gratuit (0€/mois)
        Plan::updateOrCreate(
            ['name' => 'free'],
            [
                'display_name' => 'Gratuit',
                'description' => 'Pour découvrir faktur.lu',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly' => null,
                'limits' => [
                    'max_clients' => 10,
                    'max_invoices_per_month' => 10,
                    'max_quotes_per_month' => 5,
                    'max_emails_per_month' => 10,
                    'max_expenses_per_month' => 10,
                    'max_collaborators_per_project' => 3,
                ],
                'features' => [
                    'invoices',
                    'quotes',
                    'clients',
                    'expenses',
                    '2fa',
                    'faia_export',
                ],
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        // Plan Essentiel (9€/mois)
        Plan::updateOrCreate(
            ['name' => 'essentiel'],
            [
                'display_name' => 'Essentiel',
                'description' => 'Pour les freelances et indépendants',
                'price_monthly' => 500,
                'price_yearly' => 5000,
                'stripe_price_id_monthly' => env('STRIPE_PRICE_ESSENTIEL_MONTHLY'),
                'stripe_price_id_yearly' => env('STRIPE_PRICE_ESSENTIEL_YEARLY'),
                'limits' => [
                    'max_clients' => 100,
                    'max_invoices_per_month' => 50,
                    'max_quotes_per_month' => 20,
                    'max_emails_per_month' => 100,
                    'max_expenses_per_month' => 30,
                    'max_active_projects' => 10,
                    'max_peppol_per_month' => 10,
                    'max_collaborators_per_project' => 5,
                ],
                'features' => [
                    'invoices',
                    'quotes',
                    'clients',
                    'expenses',
                    'time_tracking',
                    '2fa',
                    'projects',
                    'accounting_portal',
                    'accounting_exports',
                    'peppol_export',
                    'faia_export',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        // Plan Pro (24€/mois)
        Plan::updateOrCreate(
            ['name' => 'pro'],
            [
                'display_name' => 'Pro',
                'description' => 'Pour les PME en croissance',
                'price_monthly' => 1500,
                'price_yearly' => 15000,
                'stripe_price_id_monthly' => env('STRIPE_PRICE_PRO_MONTHLY'),
                'stripe_price_id_yearly' => env('STRIPE_PRICE_PRO_YEARLY'),
                'limits' => [
                    'max_clients' => null,
                    'max_invoices_per_month' => null,
                    'max_quotes_per_month' => null,
                    'max_emails_per_month' => null,
                    'max_expenses_per_month' => null,
                    'max_active_projects' => null,
                    'max_peppol_per_month' => null,
                    'max_collaborators_per_project' => 10,
                ],
                'features' => [
                    'invoices',
                    'quotes',
                    'clients',
                    'expenses',
                    'time_tracking',
                    '2fa',
                    'projects',
                    'accounting_portal',
                    'accounting_exports',
                    'peppol_export',
                    'hr_module',
                    'crm',
                    'peppol_transmission',
                    'faia_export',
                    'pdf_archive',
                    'email_reminders',
                    'no_branding',
                    'priority_support',
                    'organizations',
                    'facturx',
                    'advanced_reporting',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ]
        );
    }
}
