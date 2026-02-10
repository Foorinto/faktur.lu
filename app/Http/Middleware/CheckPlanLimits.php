<?php

namespace App\Http\Middleware;

use App\Services\PlanService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimits
{
    public function __construct(
        protected PlanService $planService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $limitType  The type of limit to check: clients, invoices, quotes, emails
     */
    public function handle(Request $request, Closure $next, string $limitType): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $canProceed = match ($limitType) {
            'clients' => $this->planService->canCreateClient($user),
            'invoices' => $this->planService->canCreateInvoice($user),
            'quotes' => $this->planService->canCreateQuote($user),
            'emails' => $this->planService->canSendEmail($user),
            default => true,
        };

        if (!$canProceed) {
            $message = $this->getLimitMessage($limitType);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'plan_limit_reached',
                    'message' => $message,
                    'limit_type' => $limitType,
                    'upgrade_url' => route('subscription.index'),
                ], 402);
            }

            return redirect()->back()->with('error', $message);
        }

        return $next($request);
    }

    /**
     * Get the appropriate limit message.
     */
    private function getLimitMessage(string $limitType): string
    {
        return match ($limitType) {
            'clients' => __('Vous avez atteint la limite de clients de votre plan. Passez au plan Pro pour des clients illimités.'),
            'invoices' => __('Vous avez atteint la limite de factures ce mois-ci. Passez au plan Pro pour des factures illimitées.'),
            'quotes' => __('Vous avez atteint la limite de devis ce mois-ci. Passez au plan Pro pour des devis illimités.'),
            'emails' => __('Vous avez atteint la limite d\'emails ce mois-ci. Passez au plan Pro pour des envois illimités.'),
            default => __('Vous avez atteint une limite de votre plan. Passez au plan Pro pour continuer.'),
        };
    }
}
