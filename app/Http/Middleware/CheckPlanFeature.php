<?php

namespace App\Http\Middleware;

use App\Services\PlanService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanFeature
{
    public function __construct(
        protected PlanService $planService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $feature  The feature to check
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if (!$this->planService->hasFeature($user, $feature)) {
            $message = $this->getFeatureMessage($feature);
            $minPlan = $this->getMinPlanFor($feature);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'feature_not_available',
                    'message' => $message,
                    'feature' => $feature,
                    'min_plan' => $minPlan,
                    'upgrade_url' => route('subscription.index'),
                ], 402);
            }

            // Redirect vers la page d'abonnement avec un flash structure
            // (capture par le composant UpgradeBanner.vue qui s'affiche en gros)
            return redirect()->route('subscription.index')->with('upgrade_required', [
                'feature' => $feature,
                'feature_label' => $this->getFeatureLabel($feature),
                'min_plan' => $minPlan,
                'message' => $message,
            ]);
        }

        return $next($request);
    }

    private function getMinPlanFor(string $feature): string
    {
        $proFeatures = ['pdf_archive', 'email_reminders', 'no_branding', 'organizations', 'hr_module', 'crm', 'peppol_transmission', 'facturx', 'advanced_reporting', 'priority_support'];

        return in_array($feature, $proFeatures, true) ? 'Pro' : 'Essentiel';
    }

    private function getFeatureLabel(string $feature): string
    {
        return match ($feature) {
            'pdf_archive' => __('Archivage PDF longue durée'),
            'email_reminders' => __('Relances automatiques par email'),
            'no_branding' => __('Suppression du branding faktur.lu'),
            'organizations' => __('Gestion d\'organisation'),
            'hr_module' => __('Module RH'),
            'crm' => __('CRM avancé'),
            'peppol_transmission' => __('Transmission Peppol'),
            'facturx' => __('Export Factur-X / ZUGFeRD'),
            'projects' => __('Gestion de projets'),
            'time_tracking' => __('Suivi du temps'),
            'accounting_portal' => __('Portail comptable / fiduciaire'),
            'accounting_exports' => __('Exports comptables (Sage BOB, FID-Manager)'),
            'recurring_invoices' => __('Facturation récurrente'),
            default => __('Cette fonctionnalité'),
        };
    }

    /**
     * Get the appropriate feature message.
     */
    private function getFeatureMessage(string $feature): string
    {
        return match ($feature) {
            'pdf_archive' => __('L\'archivage PDF longue durée est réservé au plan Pro.'),
            'email_reminders' => __('Les relances automatiques sont réservées au plan Pro.'),
            'no_branding' => __('La suppression du branding est réservée au plan Pro.'),
            'organizations' => __('La gestion d\'organisation est réservée au plan Pro.'),
            'hr_module' => __('Le module RH est réservé au plan Pro.'),
            'crm' => __('Le CRM avancé (interactions, rappels, tags) est réservé au plan Pro.'),
            'peppol_transmission' => __('La transmission Peppol est réservée au plan Pro.'),
            'facturx' => __('L\'export Factur-X est réservé au plan Pro.'),
            'projects' => __('La gestion de projets nécessite le plan Essentiel ou Pro.'),
            'time_tracking' => __('Le suivi du temps nécessite le plan Essentiel ou Pro.'),
            'accounting_portal' => __('Le portail comptable (invitation d\'une fiduciaire) nécessite le plan Essentiel ou Pro.'),
            'accounting_exports' => __('Les exports comptables (Sage BOB, FID-Manager, livre des recettes) nécessitent le plan Essentiel ou Pro.'),
            'recurring_invoices' => __('La facturation récurrente nécessite le plan Essentiel ou Pro. Vos récurrences existantes restent accessibles.'),
            default => __('Cette fonctionnalité nécessite un plan supérieur.'),
        };
    }
}
