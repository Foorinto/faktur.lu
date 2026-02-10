<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Inertia\Inertia;

class PricingController extends Controller
{
    /**
     * Show the public pricing page.
     */
    public function index()
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return Inertia::render('Pricing', [
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
        ]);
    }
}
