<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;

class PlanService
{
    /**
     * Get the user's current plan.
     * During trial, users get access to Pro features.
     */
    public function getUserPlan(User $user): Plan
    {
        // Pro subscribers get Pro plan
        if ($user->isPro()) {
            return Plan::pro() ?? $this->getDefaultProPlan();
        }

        // Users on generic trial get Pro features
        if ($user->isOnGenericTrial()) {
            return Plan::pro() ?? $this->getDefaultProPlan();
        }

        // Subscribed to Essentiel plan
        if ($user->isEssentiel()) {
            return Plan::essentiel() ?? $this->getDefaultEssentielPlan();
        }

        // No subscription, no trial = FREE plan
        return Plan::free() ?? $this->getDefaultFreePlan();
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
     * Check if user can create more expenses this month.
     */
    public function canCreateExpense(User $user): bool
    {
        $plan = $this->getUserPlan($user);
        $limit = $plan->getLimit('max_expenses_per_month');

        if ($limit === null) {
            return true;
        }

        $count = $user->expenses()
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        return $count < $limit;
    }

    /**
     * Check if user can create more projects.
     */
    public function canCreateProject(User $user): bool
    {
        $plan = $this->getUserPlan($user);
        $limit = $plan->getLimit('max_active_projects');

        if ($limit === null) {
            return true;
        }

        $count = \App\Models\Project::where('created_by', $user->id)
            ->whereNotIn('status', ['done', 'archived'])
            ->count();

        return $count < $limit;
    }

    /**
     * Check if user can invite a new collaborator on a given project.
     * FEAT-081: per-project quota driven by plan.max_collaborators_per_project.
     */
    public function canInviteCollaboratorToProject(User $user, \App\Models\Project $project): bool
    {
        $plan = $this->getUserPlan($user);
        $limit = $plan->getLimit('max_collaborators_per_project');

        if ($limit === null) {
            return true;
        }

        $count = \DB::table('project_members')
            ->where('project_id', $project->id)
            ->where('member_type', 'collaborator')
            ->count();

        return $count < $limit;
    }

    /**
     * Get current collaborator usage on a project.
     * Returns ['used' => int, 'max' => int|null, 'plan' => string].
     */
    public function collaboratorQuotaForProject(User $user, \App\Models\Project $project): array
    {
        $plan = $this->getUserPlan($user);
        $limit = $plan->getLimit('max_collaborators_per_project');

        $count = \DB::table('project_members')
            ->where('project_id', $project->id)
            ->where('member_type', 'collaborator')
            ->count();

        return [
            'used' => $count,
            'max' => $limit,
            'plan' => $plan->name,
        ];
    }

    /**
     * Check if user can export more Peppol this month.
     */
    public function canExportPeppol(User $user): bool
    {
        $plan = $this->getUserPlan($user);
        $limit = $plan->getLimit('max_peppol_per_month');

        if ($limit === null) {
            return true;
        }

        $count = \App\Models\PeppolTransmission::where('user_id', $user->id)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
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
            'expenses_this_month' => [
                'used' => $user->expenses()
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->count(),
                'limit' => $plan->getLimit('max_expenses_per_month'),
                'unlimited' => $plan->getLimit('max_expenses_per_month') === null,
            ],
            'active_projects' => [
                'used' => \App\Models\Project::where('created_by', $user->id)
                    ->whereNotIn('status', ['done', 'archived'])
                    ->count(),
                'limit' => $plan->getLimit('max_active_projects'),
                'unlimited' => $plan->getLimit('max_active_projects') === null,
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
            'expenses' => $stats['expenses_this_month']['unlimited']
                ? null
                : max(0, $stats['expenses_this_month']['limit'] - $stats['expenses_this_month']['used']),
            'projects' => $stats['active_projects']['unlimited']
                ? null
                : max(0, $stats['active_projects']['limit'] - $stats['active_projects']['used']),
        ];
    }

    /**
     * Get default free plan if none exists in database.
     */
    private function getDefaultFreePlan(): Plan
    {
        $plan = new Plan();
        $plan->name = 'free';
        $plan->display_name = 'Gratuit';
        $plan->limits = [
            'max_clients' => 5,
            'max_invoices_per_month' => 3,
            'max_quotes_per_month' => 2,
            'max_emails_per_month' => 5,
            'max_expenses_per_month' => 10,
        ];
        $plan->features = ['invoices', 'quotes', 'clients', 'expenses', '2fa', 'faia_export'];

        return $plan;
    }

    /**
     * Get default essentiel plan if none exists in database.
     */
    private function getDefaultEssentielPlan(): Plan
    {
        $plan = new Plan();
        $plan->name = 'essentiel';
        $plan->display_name = 'Essentiel';
        $plan->limits = [
            'max_clients' => 100,
            'max_invoices_per_month' => 50,
            'max_quotes_per_month' => 20,
            'max_emails_per_month' => 100,
            'max_expenses_per_month' => 30,
            'max_active_projects' => 10,
            'max_peppol_per_month' => 10,
        ];
        $plan->features = [
            'invoices', 'quotes', 'clients', 'expenses', 'time_tracking', '2fa',
            'projects', 'accounting_portal', 'accounting_exports', 'peppol_export', 'faia_export',
        ];

        return $plan;
    }

    /**
     * Get default pro plan if none exists in database.
     */
    private function getDefaultProPlan(): Plan
    {
        $plan = new Plan();
        $plan->name = 'pro';
        $plan->display_name = 'Pro';
        $plan->limits = null; // unlimited
        $plan->features = [
            'invoices', 'quotes', 'clients', 'expenses', 'time_tracking', '2fa',
            'projects', 'accounting_portal', 'accounting_exports', 'peppol_export',
            'hr_module', 'crm', 'peppol_transmission', 'faia_export', 'pdf_archive',
            'email_reminders', 'no_branding', 'priority_support', 'organizations',
            'facturx', 'advanced_reporting',
        ];

        return $plan;
    }
}
