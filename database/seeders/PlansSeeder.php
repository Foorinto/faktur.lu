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
                'description' => 'Pour découvrir '.config('marque.nom'),
                'price_monthly' => 0,
                'price_yearly' => 0,
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly' => null,
                'limits' => [
                    'max_clients' => 10,
                    'max_invoices_per_month' => 5,
                    'max_quotes_per_month' => 5,
                    'max_emails_per_month' => 10,
                    'max_expenses_per_month' => 10,
                    'max_collaborators_per_project' => 3,
                    'max_products' => 10,
                    // Le module RH est réservé au plan Pro : le plafond à 0 double
                    // la garde de `plan.feature:hr_module`, pour que le quota reste
                    // vrai même si l'accès à la fonctionnalité évolue.
                    'max_employees' => 0,
                    'max_accountants' => 1,
                ],
                'features' => [
                    'invoices',
                    'quotes',
                    'clients',
                    'expenses',
                    '2fa',
                    'faia_export',
                    // 'accounting_portal' réservé à Essentiel+ (les comptes existants
                    // gardent l'accès via le flag accounting_portal_grandfathered).
                ],
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        // Plan Essentiel (5 EUR/mois, 50 EUR/an)
        Plan::updateOrCreate(
            ['name' => 'essentiel'],
            [
                'display_name' => 'Essentiel',
                'description' => 'Pour les freelances et indépendants',
                'price_monthly' => 500,
                'price_yearly' => 5000,
                'stripe_price_id_monthly' => $this->priceId('essentiel', 'monthly'),
                'stripe_price_id_yearly' => $this->priceId('essentiel', 'yearly'),
                'limits' => [
                    'max_clients' => 100,
                    'max_invoices_per_month' => 50,
                    'max_quotes_per_month' => 20,
                    'max_emails_per_month' => 100,
                    'max_expenses_per_month' => 30,
                    'max_active_projects' => 10,
                    'max_peppol_per_month' => null, // aucun plafond annoncé sur la page tarifs
                    'max_collaborators_per_project' => 5,
                    'max_products' => null,
                    'max_employees' => 0,
                    // « Portail comptable (1 expert) » : le chiffre annoncé sur la
                    // page tarifs devient une règle appliquée.
                    'max_accountants' => 1,
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
                    // Le client au forfait mensuel est le cas d'usage du
                    // freelance : la récurrence reste dans le plan freelance.
                    'recurring_invoices',
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
                'stripe_price_id_monthly' => $this->priceId('pro', 'monthly'),
                'stripe_price_id_yearly' => $this->priceId('pro', 'yearly'),
                'limits' => [
                    'max_clients' => null,
                    'max_invoices_per_month' => null,
                    'max_quotes_per_month' => null,
                    'max_emails_per_month' => null,
                    'max_expenses_per_month' => null,
                    'max_active_projects' => null,
                    // 50 et non « illimité » : chaque transmission se paie au
                    // point d'accès. 15 € ÷ 0,30 € (pire tarif que nous
                    // paierions) = 50, seuil exact où la marge s'annule.
                    // L'export manuel du XML, lui, reste illimité.
                    'max_peppol_per_month' => 50,
                    'max_collaborators_per_project' => 10,
                    'max_products' => null,
                    // Les deux plafonds annoncés sur les pages PME et tarifs :
                    // « module RH jusqu'à 15 employés », « 3 comptables externes ».
                    'max_employees' => 15,
                    'max_accountants' => 3,
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
                    'recurring_invoices',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        $this->warnAboutMissingStripePrices();
    }

    /**
     * Price ID Stripe d'un plan payant.
     *
     * Ne renvoie jamais une valeur vide si la base en contient déjà une : ce
     * seeder est rejoué à chaque déploiement, et une variable d'environnement
     * absente écraserait sinon un price ID valide — rendant le plan
     * insouscriptible ("Configuration de paiement manquante") sans autre signe.
     */
    private function priceId(string $plan, string $period): ?string
    {
        $value = config("services.stripe.{$plan}_{$period}");

        if (filled($value)) {
            return $value;
        }

        return Plan::where('name', $plan)->value("stripe_price_id_{$period}");
    }

    /**
     * Un plan payant sans price ID ne peut pas être souscrit, et le symptôme
     * n'apparaît qu'au moment où un client tente de payer. On le signale donc
     * bruyamment au déploiement.
     */
    private function warnAboutMissingStripePrices(): void
    {
        $missing = [];

        foreach (Plan::whereIn('name', ['essentiel', 'pro'])->get() as $plan) {
            foreach (['monthly', 'yearly'] as $period) {
                if (blank($plan->{"stripe_price_id_{$period}"})) {
                    $missing[] = sprintf(
                        '%s (%s) → STRIPE_%s_%s_PRICE_ID',
                        $plan->name,
                        $period,
                        strtoupper($plan->name),
                        strtoupper($period)
                    );
                }
            }
        }

        if ($missing === []) {
            return;
        }

        $this->command?->warn('⚠️  Price ID Stripe manquant — ces plans ne pourront pas être souscrits :');
        foreach ($missing as $line) {
            $this->command?->warn('   - '.$line);
        }
        $this->command?->warn('   Renseignez ces variables dans le .env, puis rejouez : php artisan db:seed --class=PlansSeeder --force');
    }
}
