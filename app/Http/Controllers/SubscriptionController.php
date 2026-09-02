<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\PlanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
                    'url' => $invoice->asStripeInvoice()->invoice_pdf,
                ])
                : [],
            'onTrial' => $user->isOnGenericTrial(),
            'trialEndsAt' => $user->trial_ends_at,
            'trialDaysRemaining' => $user->trialDaysRemaining(),
        ]);
    }

    /**
     * Create a Stripe Checkout session for subscription.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan' => 'required|string|in:essentiel,pro',
            'billing_period' => 'required|string|in:monthly,yearly',
        ]);

        $user = $request->user();
        $plan = Plan::where('name', $request->plan)->firstOrFail();

        $priceId = $request->billing_period === 'yearly'
            ? $plan->stripe_price_id_yearly
            : $plan->stripe_price_id_monthly;

        if (!$priceId) {
            return back()->with('error', __('app.subscription_flash.error_payment_config_missing'));
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
     *
     * Fallback : si le webhook Stripe n'a pas mis a jour la subscription localement
     * (webhook mal configure, retard reseau, etc.), on synchronise manuellement
     * a partir du session_id retourne par Stripe Checkout.
     */
    public function success(Request $request)
    {
        $user = $request->user();
        $sessionId = $request->query('session_id');

        if ($sessionId) {
            try {
                $this->syncSubscriptionFromCheckoutSession($user, $sessionId);
            } catch (\Throwable $e) {
                Log::error('Subscription sync from checkout session failed', [
                    'user_id' => $user->id,
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('subscription.index')
            ->with('success', __('app.subscription_flash.activated'));
    }

    /**
     * Recupere la session Stripe Checkout et cree/met a jour la subscription locale.
     * Reproduit la logique du WebhookController de Cashier (handleCustomerSubscriptionCreated)
     * pour ne pas dependre de l'arrivee du webhook.
     */
    protected function syncSubscriptionFromCheckoutSession($user, string $sessionId): void
    {
        $stripe = $user->stripe();
        $session = $stripe->checkout->sessions->retrieve($sessionId, ['expand' => ['subscription']]);

        if (! $session || ! $session->subscription) {
            return;
        }

        // Lier le customer Stripe a l'utilisateur si pas deja fait
        if ($session->customer && ! $user->stripe_id) {
            $user->stripe_id = is_string($session->customer) ? $session->customer : $session->customer->id;
            $user->save();
        }

        $stripeSubscription = is_string($session->subscription)
            ? $stripe->subscriptions->retrieve($session->subscription, ['expand' => ['items.data']])
            : $session->subscription;

        if (! $stripeSubscription) {
            return;
        }

        $firstItem = $stripeSubscription->items->data[0] ?? null;
        if (! $firstItem) {
            return;
        }

        $isSinglePrice = count($stripeSubscription->items->data) === 1;
        $trialEndsAt = $stripeSubscription->trial_end
            ? Carbon::createFromTimestamp($stripeSubscription->trial_end)
            : null;

        $subscription = $user->subscriptions()->updateOrCreate(
            ['stripe_id' => $stripeSubscription->id],
            [
                'type' => 'default',
                'stripe_status' => $stripeSubscription->status,
                'stripe_price' => $isSinglePrice ? $firstItem->price->id : null,
                'quantity' => $isSinglePrice && isset($firstItem->quantity) ? $firstItem->quantity : null,
                'trial_ends_at' => $trialEndsAt,
                'ends_at' => null,
            ]
        );

        foreach ($stripeSubscription->items->data as $item) {
            $subscription->items()->updateOrCreate(
                ['stripe_id' => $item->id],
                [
                    'stripe_product' => $item->price->product,
                    'stripe_price' => $item->price->id,
                    'quantity' => $item->quantity ?? 1,
                ]
            );
        }

        Log::info('Subscription manually synced from checkout session', [
            'user_id' => $user->id,
            'subscription_id' => $stripeSubscription->id,
            'status' => $stripeSubscription->status,
        ]);
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
            return back()->with('error', __('app.subscription_flash.error_no_active'));
        }

        $user->subscription('default')->cancel();

        return back()->with('success', __('app.subscription_flash.cancelled'));
    }

    /**
     * Resume a cancelled subscription.
     */
    public function resume(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        if (!$subscription || !$subscription->onGracePeriod()) {
            return back()->with('error', __('app.subscription_flash.error_cannot_resume'));
        }

        $subscription->resume();

        return back()->with('success', __('app.subscription_flash.resumed'));
    }

    /**
     * Swap to a different plan or billing period.
     */
    public function swap(Request $request)
    {
        $request->validate([
            'plan' => 'sometimes|string|in:essentiel,pro',
            'billing_period' => 'required|string|in:monthly,yearly',
        ]);

        $user = $request->user();
        $planName = $request->input('plan', $user->isPro() ? 'pro' : 'essentiel');
        $plan = Plan::where('name', $planName)->first();

        if (!$plan) {
            return back()->with('error', __('app.subscription_flash.error_plan_not_found'));
        }

        $priceId = $request->billing_period === 'yearly'
            ? $plan->stripe_price_id_yearly
            : $plan->stripe_price_id_monthly;

        if (!$priceId) {
            return back()->with('error', __('app.subscription_flash.error_payment_config'));
        }

        try {
            $user->subscription('default')->swap($priceId);

            return back()->with('success', __('app.subscription_flash.updated'));
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
        $user = $request->user();
        $planName = $user->isPro() ? 'Pro' : ($user->isEssentiel() ? 'Essentiel' : config('marque.nom'));

        return $user->downloadInvoice($invoiceId, [
            'vendor' => config('marque.nom'),
            'product' => "Abonnement {$planName}",
        ]);
    }
}
