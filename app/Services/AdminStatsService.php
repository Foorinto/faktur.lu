<?php

namespace App\Services;

use App\Models\User;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;

class AdminStatsService
{
    /**
     * Get user statistics.
     */
    public function getUserStats(): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        return [
            'total' => User::count(),
            'verified' => User::whereNotNull('email_verified_at')->count(),
            'unverified' => User::whereNull('email_verified_at')->count(),
            'with_2fa' => User::whereNotNull('two_factor_confirmed_at')->count(),
            'new_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
            'new_last_30_days' => User::where('created_at', '>=', $thirtyDaysAgo)->count(),
        ];
    }

    /**
     * Répartition des inscrits par secteur d'activité.
     *
     * C'est la raison d'être de l'écran de sélection : décider quel pack métier
     * mérite d'être construit, sur des comptes plutôt que sur des intuitions.
     *
     * Les comptes sans réponse sont comptés à part et jamais fondus dans
     * « Autre » : les uns n'ont pas été interrogés — tous ceux d'avant août
     * 2026 — les autres ont répondu. Les additionner ferait paraître le secteur
     * générique majoritaire alors qu'il ne l'est pas.
     *
     * @return array{par_secteur: array<int, array{secteur: string, libelle: string, total: int}>, sans_reponse: int, repondants: int}
     */
    public function getSectorStats(): array
    {
        $parSecteur = User::query()
            ->whereNotNull('business_sector')
            ->selectRaw('business_sector, COUNT(*) as total')
            ->groupBy('business_sector')
            ->orderByDesc('total')
            ->get()
            // Le libellé est résolu ici : le panneau d'administration est servi
            // en français et ne charge pas le dictionnaire du front.
            ->map(fn ($l) => [
                'secteur' => $l->business_sector,
                'libelle' => __("app.business_sectors.{$l->business_sector}.label", [], 'fr'),
                'total' => (int) $l->total,
            ])
            ->all();

        return [
            'par_secteur' => $parSecteur,
            'sans_reponse' => User::whereNull('business_sector')->count(),
            'repondants' => array_sum(array_column($parSecteur, 'total')),
        ];
    }

    /**
     * Get invoice statistics.
     */
    public function getInvoiceStats(): array
    {
        return [
            'total' => Invoice::count(),
            'this_month' => Invoice::where('created_at', '>=', now()->startOfMonth())->count(),
            'finalized' => Invoice::whereNotNull('finalized_at')->count(),
            'draft' => Invoice::whereNull('finalized_at')->count(),
            'paid' => Invoice::where('status', 'paid')->count(),
        ];
    }

    /**
     * Get revenue statistics.
     */
    public function getRevenueStats(): array
    {
        $totalRevenue = Invoice::whereNotNull('finalized_at')
            ->where('type', 'invoice')
            ->sum('total_ttc');

        $totalCredits = Invoice::whereNotNull('finalized_at')
            ->where('type', 'credit_note')
            ->sum('total_ttc');

        $monthRevenue = Invoice::whereNotNull('finalized_at')
            ->where('type', 'invoice')
            ->where('finalized_at', '>=', now()->startOfMonth())
            ->sum('total_ttc');

        return [
            'total_revenue' => $totalRevenue - $totalCredits,
            'total_invoiced' => $totalRevenue,
            'total_credits' => $totalCredits,
            'this_month' => $monthRevenue,
        ];
    }

    /**
     * Get monthly user growth for chart.
     */
    public function getUserGrowthChart(int $months = 12): array
    {
        $data = [];
        $startDate = now()->subMonths($months - 1)->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $monthStart = $startDate->copy()->addMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();

            $count = User::whereBetween('created_at', [$monthStart, $monthEnd])->count();

            $data[] = [
                'month' => $monthStart->format('M Y'),
                'count' => $count,
            ];
        }

        return $data;
    }

    /**
     * Get monthly revenue chart.
     */
    public function getRevenueChart(int $months = 12): array
    {
        $data = [];
        $startDate = now()->subMonths($months - 1)->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $monthStart = $startDate->copy()->addMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();

            $revenue = Invoice::whereNotNull('finalized_at')
                ->where('type', 'invoice')
                ->whereBetween('finalized_at', [$monthStart, $monthEnd])
                ->sum('total_ttc');

            $data[] = [
                'month' => $monthStart->format('M Y'),
                'revenue' => round($revenue, 2),
            ];
        }

        return $data;
    }

    /**
     * Get recent users.
     */
    public function getRecentUsers(int $limit = 10): array
    {
        return User::select(['id', 'name', 'email', 'created_at', 'email_verified_at'])
            ->withCount(['userInvoices', 'clients'])
            ->latest()
            ->take($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get top users by invoice count.
     */
    public function getTopUsers(int $limit = 10): array
    {
        return User::select(['id', 'name', 'email'])
            ->withCount('userInvoices')
            ->withSum(['userInvoices' => function ($query) {
                $query->whereNotNull('finalized_at')->where('type', 'invoice');
            }], 'total_ttc')
            ->orderByDesc('user_invoices_count')
            ->take($limit)
            ->get()
            ->toArray();
    }
}
