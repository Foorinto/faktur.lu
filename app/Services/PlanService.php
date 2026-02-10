<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;

class PlanService
{
    /**
     * Get the user's current plan.
     */
    public function getUserPlan(User $user): Plan
    {
        if ($user->isPro()) {
            return Plan::pro() ?? $this->getDefaultStarterPlan();
        }

        return Plan::starter() ?? $this->getDefaultStarterPlan();
    }

    /**
     * Check if user can create more clients.
     */
    public function canCreateClient(User $user): bool
    {
        $plan = $this->getUserPlan($user);
        $limit = $plan->getLimit('max_clients');

        if ($limit === null) {
            return true; // unlimited
        }

        return $user->clients()->count() < $limit;
    }

    /**
     * Check if user can create more invoices this month.
     */
    public function canCreateInvoice(User $user): bool
    {
        $plan = $this->getUserPlan($user);
        $limit = $plan->getLimit('max_invoices_per_month');

        if ($limit === null) {
            return true; // unlimited
        }

        $count = $user->userInvoices()
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        return $count < $limit;
    }

    /**
     * Check if user can create more quotes this month.
     */
    public function canCreateQuote(User $user): bool
    {
        $plan = $this->getUserPlan($user);
        $limit = $plan->getLimit('max_quotes_per_month');

        if ($limit === null) {
            return true; // unlimited
        }

        $count = $user->quotes()
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        return $count < $limit;
    }

    /**
     * Check if user can send more emails this month.
     */
    public function canSendEmail(User $user): bool
    {
        $plan = $this->getUserPlan($user);
        $limit = $plan->getLimit('max_emails_per_month');

        if ($limit === null) {
            return true; // unlimited
        }

        // Count sent emails this month from invoice_emails table
        $count = $user->userInvoices()
            ->join('invoice_emails', 'invoices.id', '=', 'invoice_emails.invoice_id')
            ->whereMonth('invoice_emails.created_at', Carbon::now()->month)
            ->whereYear('invoice_emails.created_at', Carbon::now()->year)
            ->count();

        return $count < $limit;
    }

    /**
     * Check if user has a specific feature.
     */
    public function hasFeature(User $user, string $feature): bool
    {
        $plan = $this->getUserPlan($user);

        return $plan->hasFeature($feature);
    }

    /**
     * Get usage statistics for the user.
     */
    public function getUsageStats(User $user): array
    {
        $plan = $this->getUserPlan($user);

        return [
            'plan' => $plan->name,
            'plan_display_name' => $plan->display_name,
            'clients' => [
                'used' => $user->clients()->count(),
                'limit' => $plan->getLimit('max_clients'),
                'unlimited' => $plan->getLimit('max_clients') === null,
            ],
            'invoices_this_month' => [
                'used' => $user->userInvoices()
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->count(),
                'limit' => $plan->getLimit('max_invoices_per_month'),
                'unlimited' => $plan->getLimit('max_invoices_per_month') === null,
            ],
            'quotes_this_month' => [
                'used' => $user->quotes()
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->count(),
                'limit' => $plan->getLimit('max_quotes_per_month'),
                'unlimited' => $plan->getLimit('max_quotes_per_month') === null,
            ],
            'features' => $plan->features ?? [],
        ];
    }

    /**
     * Get remaining counts for user's limits.
     */
    public function getRemainingCounts(User $user): array
    {
        $stats = $this->getUsageStats($user);

        return [
            'clients' => $stats['clients']['unlimited']
                ? null
                : max(0, $stats['clients']['limit'] - $stats['clients']['used']),
            'invoices' => $stats['invoices_this_month']['unlimited']
                ? null
                : max(0, $stats['invoices_this_month']['limit'] - $stats['invoices_this_month']['used']),
            'quotes' => $stats['quotes_this_month']['unlimited']
                ? null
                : max(0, $stats['quotes_this_month']['limit'] - $stats['quotes_this_month']['used']),
        ];
    }

    /**
     * Get default starter plan if none exists in database.
     */
    private function getDefaultStarterPlan(): Plan
    {
        $plan = new Plan();
        $plan->name = 'starter';
        $plan->display_name = 'Starter';
        $plan->limits = [
            'max_clients' => 5,
            'max_invoices_per_month' => 3,
            'max_quotes_per_month' => 3,
            'max_emails_per_month' => 5,
        ];
        $plan->features = ['invoices', 'quotes', 'clients', 'expenses', 'time_tracking', '2fa'];

        return $plan;
    }
}
