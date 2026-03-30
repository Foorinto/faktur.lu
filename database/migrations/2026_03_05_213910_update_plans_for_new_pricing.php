<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create FREE plan
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
                    'max_clients' => 5,
                    'max_invoices_per_month' => 3,
                    'max_quotes_per_month' => 2,
                    'max_emails_per_month' => 5,
                    'max_expenses_per_month' => 10,
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

        // 2. Update ESSENTIEL plan (4€ → 5€)
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

        // 3. Update PRO plan (9€ → 15€)
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

    public function down(): void
    {
        // Remove free plan
        Plan::where('name', 'free')->delete();

        // Restore Essentiel to original pricing
        Plan::where('name', 'essentiel')->update([
            'price_monthly' => 400,
            'price_yearly' => 4000,
        ]);

        // Restore Pro to original pricing
        Plan::where('name', 'pro')->update([
            'price_monthly' => 900,
            'price_yearly' => 9000,
        ]);
    }
};
