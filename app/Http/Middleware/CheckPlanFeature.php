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
     * @param  string  $feature  The feature to check: faia_export, pdf_archive, email_reminders, no_branding
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
            'faia_export' => __('L\'export FAIA est réservé au plan Pro. Passez au plan Pro pour accéder à cette fonctionnalité.'),
            'pdf_archive' => __('L\'archivage PDF longue durée est réservé au plan Pro.'),
            'email_reminders' => __('Les relances automatiques sont réservées au plan Pro.'),
            'no_branding' => __('La suppression du branding est réservée au plan Pro.'),
            default => __('Cette fonctionnalité est réservée au plan Pro.'),
        };
    }
}
