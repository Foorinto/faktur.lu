<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Cashier\Exceptions\IncompletePayment;

class SubscriptionController extends Controller
{
    public function __construct(
        protected PlanService $planService
    ) {}

    /**
     * Show the subscription management page.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return Inertia::render('Settings/Subscription', [
            'plans' => $plans->map(fn ($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'display_name' => $plan->display_name,
                'description' => $plan->description,
                'price_monthly' => $plan->price_monthly_euros,
                'price_yearly' => $plan->price_yearly_euros,
                'monthly_price_when_yearly' => $plan->monthly_price_when_yearly,
                'limits' => $plan->limits,
                'features' => $plan->features,
                'is_free' => $plan->isFree(),
            ]),
            'currentPlan' => $user->plan,
            'subscription' => $user->subscription('default'),
            'usage' => $this->planService->getUsageStats($user),
            'invoices' => $user->hasStripeId()
                ? collect($user->invoices())->map(fn ($invoice) => [
                    'id' => $invoice->id,
                    'date' => $invoice->date()->toFormattedDateString(),
                    'total' => $invoice->total(),
                    'url' => $invoice->invoicePdf(),
                ])
                : [],
            'onTrial' => $user->isOnTrial(),
            'trialEndsAt' => $user->subscription('default')?->trial_ends_at,
        ]);
    }

    /**
     * Create a Stripe Checkout session for subscription.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan' => 'required|string|in:pro',
            'billing_period' => 'required|string|in:monthly,yearly',
        ]);

        $user = $request->user();
        $plan = Plan::where('name', $request->plan)->firstOrFail();

        $priceId = $request->billing_period === 'yearly'
            ? $plan->stripe_price_id_yearly
            : $plan->stripe_price_id_monthly;

        if (!$priceId) {
            return back()->with('error', __('Configuration de paiement manquante. Contactez le support.'));
        }

        return $user->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('subscription.index'),
                'locale' => 'fr',
                'billing_address_collection' => 'required',
                'customer_update' => [
                    'address' => 'auto',
                    'name' => 'auto',
                ],
            ]);
    }

    /**
     * Handle successful checkout.
     */
    public function success(Request $request)
    {
        return redirect()->route('subscription.index')
            ->with('success', __('Félicitations ! Vous êtes maintenant sur le plan Pro.'));
    }

    /**
     * Redirect to Stripe Customer Portal.
     */
    public function portal(Request $request)
    {
        return $request->user()->redirectToBillingPortal(route('subscription.index'));
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(Request $request)
    {
        $user = $request->user();

        if (!$user->subscribed('default')) {
            return back()->with('error', __('Vous n\'avez pas d\'abonnement actif.'));
        }

        $user->subscription('default')->cancel();

        return back()->with('success', __('Votre abonnement a été annulé. Vous conservez l\'accès jusqu\'à la fin de la période.'));
    }

    /**
     * Resume a cancelled subscription.
     */
    public function resume(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        if (!$subscription || !$subscription->onGracePeriod()) {
            return back()->with('error', __('Impossible de reprendre l\'abonnement.'));
        }

        $subscription->resume();

        return back()->with('success', __('Votre abonnement a été réactivé.'));
    }

    /**
     * Swap to a different billing period.
     */
    public function swap(Request $request)
    {
        $request->validate([
            'billing_period' => 'required|string|in:monthly,yearly',
        ]);

        $user = $request->user();
        $plan = Plan::pro();

        if (!$plan) {
            return back()->with('error', __('Plan non trouvé.'));
        }

        $priceId = $request->billing_period === 'yearly'
            ? $plan->stripe_price_id_yearly
            : $plan->stripe_price_id_monthly;

        if (!$priceId) {
            return back()->with('error', __('Configuration de paiement manquante.'));
        }

        try {
            $user->subscription('default')->swap($priceId);

            return back()->with('success', __('Votre abonnement a été modifié.'));
        } catch (IncompletePayment $e) {
            return redirect()->route('cashier.payment', [
                'id' => $e->payment->id,
                'redirect' => route('subscription.index'),
            ]);
        }
    }

    /**
     * Download a specific invoice.
     */
    public function downloadInvoice(Request $request, string $invoiceId)
    {
        return $request->user()->downloadInvoice($invoiceId, [
            'vendor' => 'faktur.lu',
            'product' => 'Abonnement Pro',
        ]);
    }
}
