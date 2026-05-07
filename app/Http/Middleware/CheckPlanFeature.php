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

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'feature_not_available',
                    'message' => $message,
                    'feature' => $feature,
                    'upgrade_url' => route('subscription.index'),
                ], 402);
            }

            return redirect()->back()->with('error', $message);
        }

        return $next($request);
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
            default => __('Cette fonctionnalité nécessite un plan supérieur.'),
        };
    }
}
