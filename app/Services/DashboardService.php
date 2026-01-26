<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\TimeEntry;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Luxembourg VAT franchise threshold (Article 57).
     */
    public const VAT_FRANCHISE_THRESHOLD = 35000;

    /**
     * Luxembourg simplified accounting threshold.
     */
    public const SIMPLIFIED_ACCOUNTING_THRESHOLD = 100000;

    /**
     * Get all KPIs for the dashboard.
     */
    public function getKpis(int $year): array
    {
        $annualRevenue = $this->getAnnualRevenue($year);
        $previousYearRevenue = $this->getAnnualRevenue($year - 1);
        $annualExpenses = $this->getAnnualExpenses($year);
        $previousYearExpenses = $this->getAnnualExpenses($year - 1);

        $netProfit = $annualRevenue - $annualExpenses;
        $previousNetProfit = $previousYearRevenue - $previousYearExpenses;

        return [
            'annual_revenue' => round($annualRevenue, 2),
            'annual_revenue_change' => $this->calculatePercentageChange($annualRevenue, $previousYearRevenue),
            'annual_expenses' => round($annualExpenses, 2),
            'net_profit' => round($netProfit, 2),
            'net_profit_change' => $this->calculatePercentageChange($netProfit, $previousNetProfit),
            'vat_franchise_threshold' => self::VAT_FRANCHISE_THRESHOLD,
            'vat_franchise_progress' => $this->getVatFranchiseProgress($annualRevenue),
            'simplified_accounting_threshold' => self::SIMPLIFIED_ACCOUNTING_THRESHOLD,
            'simplified_accounting_progress' => $this->getSimplifiedAccountingProgress($annualRevenue),
            'unpaid_invoices' => $this->getUnpaidInvoicesStats(),
            'unbilled_time' => $this->getUnbilledTimeStats(),
            'vat_summary' => $this->getVatSummary($year),
            'alerts' => $this->getAlerts($year, $annualRevenue),
        ];
    }

    /**
     * Get annual revenue (total HT from finalized invoices).
     */
    public function getAnnualRevenue(int $year): float
    {
        return (float) Invoice::whereYear('issued_at', $year)
            ->whereIn('status', [
                Invoice::STATUS_FINALIZED,
                Invoice::STATUS_SENT,
                Invoice::STATUS_PAID,
            ])
            ->where('type', Invoice::TYPE_INVOICE)
            ->sum('total_ht');
    }

    /**
     * Get annual expenses (total HT).
     */
    public function getAnnualExpenses(int $year): float
    {
        return (float) Expense::whereYear('date', $year)
            ->sum('amount_ht');
    }

    /**
     * Get VAT franchise progress percentage.
     */
    public function getVatFranchiseProgress(float $revenue): array
    {
        $percentage = min(100, ($revenue / self::VAT_FRANCHISE_THRESHOLD) * 100);

        return [
            'current' => $revenue,
            'threshold' => self::VAT_FRANCHISE_THRESHOLD,
            'percentage' => round($percentage, 1),
            'remaining' => max(0, self::VAT_FRANCHISE_THRESHOLD - $revenue),
        ];
    }

    /**
     * Get simplified accounting progress percentage.
     */
    public function getSimplifiedAccountingProgress(float $revenue): array
    {
        $percentage = min(100, ($revenue / self::SIMPLIFIED_ACCOUNTING_THRESHOLD) * 100);

        return [
            'current' => $revenue,
            'threshold' => self::SIMPLIFIED_ACCOUNTING_THRESHOLD,
            'percentage' => round($percentage, 1),
            'remaining' => max(0, self::SIMPLIFIED_ACCOUNTING_THRESHOLD - $revenue),
        ];
    }

    /**
     * Get unpaid invoices statistics.
     */
    public function getUnpaidInvoicesStats(): array
    {
        $unpaidInvoices = Invoice::whereIn('status', [
            Invoice::STATUS_FINALIZED,
            Invoice::STATUS_SENT,
        ])
            ->where('type', Invoice::TYPE_INVOICE)
            ->get();

        $overdueInvoices = $unpaidInvoices->filter(function ($invoice) {
            return $invoice->due_at && $invoice->due_at->isPast();
        });

        return [
            'count' => $unpaidInvoices->count(),
            'total_amount' => round($unpaidInvoices->sum('total_ttc'), 2),
            'overdue_count' => $overdueInvoices->count(),
            'overdue_amount' => round($overdueInvoices->sum('total_ttc'), 2),
        ];
    }

    /**
     * Get unpaid invoices list.
     */
    public function getUnpaidInvoices(int $limit = 5): array
    {
        return Invoice::with('client:id,name')
            ->whereIn('status', [
                Invoice::STATUS_FINALIZED,
                Invoice::STATUS_SENT,
            ])
            ->where('type', Invoice::TYPE_INVOICE)
            ->orderBy('due_at')
            ->limit($limit)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'client_name' => $invoice->client->name ?? 'N/A',
                    'total_ttc' => $invoice->total_ttc,
                    'due_at' => $invoice->due_at?->format('Y-m-d'),
                    'is_overdue' => $invoice->due_at && $invoice->due_at->isPast(),
                    'days_overdue' => $invoice->due_at && $invoice->due_at->isPast()
                        ? (int) $invoice->due_at->diffInDays(now())
                        : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get unbilled time statistics.
     */
    public function getUnbilledTimeStats(): array
    {
        $unbilledSeconds = TimeEntry::unbilled()
            ->stopped()
            ->sum('duration_seconds');

        return [
            'total_seconds' => $unbilledSeconds,
            'formatted' => TimeEntry::formatSeconds($unbilledSeconds),
            'hours' => round($unbilledSeconds / 3600, 2),
        ];
    }

    /**
     * Get unbilled time by client.
     */
    public function getUnbilledTimeByClient(int $limit = 5): array
    {
        return TimeEntry::unbilled()
            ->stopped()
            ->select('client_id', DB::raw('SUM(duration_seconds) as total_seconds'))
            ->groupBy('client_id')
            ->with('client:id,name,default_hourly_rate')
            ->orderByDesc('total_seconds')
            ->limit($limit)
            ->get()
            ->map(function ($entry) {
                $estimatedAmount = ($entry->total_seconds / 3600) * ($entry->client->default_hourly_rate ?? 0);

                return [
                    'client_id' => $entry->client_id,
                    'client_name' => $entry->client->name ?? 'N/A',
                    'total_seconds' => $entry->total_seconds,
                    'formatted' => TimeEntry::formatSeconds($entry->total_seconds),
                    'hours' => round($entry->total_seconds / 3600, 2),
                    'estimated_amount' => round($estimatedAmount, 2),
                    'hourly_rate' => $entry->client->default_hourly_rate ?? 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get VAT summary for the year.
     */
    public function getVatSummary(int $year): array
    {
        // VAT collected from invoices
        $vatCollected = (float) Invoice::whereYear('issued_at', $year)
            ->whereIn('status', [
                Invoice::STATUS_FINALIZED,
                Invoice::STATUS_SENT,
                Invoice::STATUS_PAID,
            ])
            ->where('type', Invoice::TYPE_INVOICE)
            ->sum('total_vat');

        // VAT deductible from expenses
        $vatDeductible = (float) Expense::whereYear('date', $year)
            ->where('is_deductible', true)
            ->sum('amount_vat');

        $vatBalance = $vatCollected - $vatDeductible;

        return [
            'collected' => round($vatCollected, 2),
            'deductible' => round($vatDeductible, 2),
            'balance' => round($vatBalance, 2),
            'to_pay' => $vatBalance > 0 ? round($vatBalance, 2) : 0,
            'credit' => $vatBalance < 0 ? round(abs($vatBalance), 2) : 0,
        ];
    }

    /**
     * Get monthly revenue chart data.
     */
    public function getRevenueChart(int $year): array
    {
        $monthlyData = Invoice::whereYear('issued_at', $year)
            ->whereIn('status', [
                Invoice::STATUS_FINALIZED,
                Invoice::STATUS_SENT,
                Invoice::STATUS_PAID,
            ])
            ->where('type', Invoice::TYPE_INVOICE)
            ->selectRaw('CAST(strftime("%m", issued_at) AS INTEGER) as month, SUM(total_ht) as revenue')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('revenue', 'month')
            ->toArray();

        // Fill in all 12 months
        $chartData = [];
        $months = [
            1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Août',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc',
        ];

        foreach ($months as $num => $label) {
            $chartData[] = [
                'month' => $num,
                'label' => $label,
                'revenue' => round((float) ($monthlyData[$num] ?? 0), 2),
            ];
        }

        return $chartData;
    }

    /**
     * Get alerts based on thresholds.
     */
    public function getAlerts(int $year, float $annualRevenue): array
    {
        $alerts = [];

        // VAT franchise threshold alert
        $vatPercentage = ($annualRevenue / self::VAT_FRANCHISE_THRESHOLD) * 100;

        if ($vatPercentage >= 100) {
            $alerts[] = [
                'type' => 'vat_threshold_exceeded',
                'level' => 'critical',
                'title' => 'Seuil de franchise TVA dépassé',
                'message' => sprintf(
                    'Votre CA annuel (%.0f €) a dépassé le seuil de franchise TVA (%.0f €). Vous devez vous inscrire comme assujetti TVA.',
                    $annualRevenue,
                    self::VAT_FRANCHISE_THRESHOLD
                ),
            ];
        } elseif ($vatPercentage >= 80) {
            $alerts[] = [
                'type' => 'vat_threshold_warning',
                'level' => 'warning',
                'title' => 'Proche du seuil de franchise TVA',
                'message' => sprintf(
                    'Votre CA annuel (%.0f €) approche du seuil de franchise TVA (%.0f €). Il reste %.0f € avant le seuil.',
                    $annualRevenue,
                    self::VAT_FRANCHISE_THRESHOLD,
                    self::VAT_FRANCHISE_THRESHOLD - $annualRevenue
                ),
            ];
        }

        // Simplified accounting threshold alert
        $simplifiedPercentage = ($annualRevenue / self::SIMPLIFIED_ACCOUNTING_THRESHOLD) * 100;

        if ($simplifiedPercentage >= 100) {
            $alerts[] = [
                'type' => 'accounting_threshold_exceeded',
                'level' => 'critical',
                'title' => 'Seuil de comptabilité simplifiée dépassé',
                'message' => sprintf(
                    'Votre CA annuel (%.0f €) a dépassé le seuil de comptabilité simplifiée (%.0f €). Vous devez tenir une comptabilité complète.',
                    $annualRevenue,
                    self::SIMPLIFIED_ACCOUNTING_THRESHOLD
                ),
            ];
        } elseif ($simplifiedPercentage >= 80) {
            $alerts[] = [
                'type' => 'accounting_threshold_warning',
                'level' => 'warning',
                'title' => 'Proche du seuil de comptabilité simplifiée',
                'message' => sprintf(
                    'Votre CA annuel (%.0f €) approche du seuil de comptabilité simplifiée (%.0f €).',
                    $annualRevenue,
                    self::SIMPLIFIED_ACCOUNTING_THRESHOLD
                ),
            ];
        }

        // Overdue invoices alert
        $overdueCount = Invoice::whereIn('status', [Invoice::STATUS_FINALIZED, Invoice::STATUS_SENT])
            ->where('type', Invoice::TYPE_INVOICE)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        if ($overdueCount > 0) {
            $alerts[] = [
                'type' => 'overdue_invoices',
                'level' => 'warning',
                'title' => 'Factures en retard',
                'message' => sprintf(
                    'Vous avez %d facture%s en retard de paiement.',
                    $overdueCount,
                    $overdueCount > 1 ? 's' : ''
                ),
            ];
        }

        return $alerts;
    }

    /**
     * Get recent invoices.
     */
    public function getRecentInvoices(int $limit = 5): array
    {
        return Invoice::with('client:id,name')
            ->whereIn('status', [
                Invoice::STATUS_FINALIZED,
                Invoice::STATUS_SENT,
                Invoice::STATUS_PAID,
            ])
            ->orderByDesc('issued_at')
            ->limit($limit)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'client_name' => $invoice->client->name ?? 'N/A',
                    'total_ttc' => $invoice->total_ttc,
                    'status' => $invoice->status,
                    'issued_at' => $invoice->issued_at?->format('Y-m-d'),
                    'is_credit_note' => $invoice->isCreditNote(),
                ];
            })
            ->toArray();
    }

    /**
     * Calculate percentage change between two values.
     */
    protected function calculatePercentageChange(float $current, float $previous): ?float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Get available years for filtering.
     */
    public function getAvailableYears(): array
    {
        $invoiceYears = Invoice::selectRaw('DISTINCT CAST(strftime("%Y", issued_at) AS INTEGER) as year')
            ->whereNotNull('issued_at')
            ->pluck('year');

        $expenseYears = Expense::selectRaw('DISTINCT CAST(strftime("%Y", date) AS INTEGER) as year')
            ->pluck('year');

        $years = $invoiceYears->merge($expenseYears)
            ->unique()
            ->filter()
            ->sort()
            ->reverse()
            ->values();

        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        return $years->toArray();
    }
}
