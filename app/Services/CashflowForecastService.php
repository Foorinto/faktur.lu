<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;

class CashflowForecastService
{
    /**
     * Get the full cashflow forecast data.
     */
    public function getForecast(int $days = 90): array
    {
        $hasData = Invoice::unpaid()->invoicesOnly()->exists();

        if (! $hasData) {
            return [
                'has_data' => false,
                'timeline' => [],
                'summary' => ['current' => 0, 'days_30' => null, 'days_60' => null, 'days_90' => null],
                'totals' => ['total_expected_income' => 0, 'total_expected_expense' => 0, 'monthly_expense_average' => 0],
                'period_days' => $days,
            ];
        }

        $incomeByDay = $this->getUnpaidInvoicesByDay($days);
        $monthlyExpense = $this->getAverageMonthlyExpense();
        $dailyExpense = (float) bcdiv((string) $monthlyExpense, '30', 4);

        $timeline = $this->buildDailyTimeline($days, $incomeByDay, $dailyExpense);
        $summary = $this->getSummaryAtMilestones($timeline);

        $totalIncome = 0;
        foreach ($timeline as $day) {
            $totalIncome = (float) bcadd((string) $totalIncome, (string) $day['income'], 4);
        }
        $totalExpense = (float) bcmul((string) $dailyExpense, (string) ($days + 1), 4);

        return [
            'has_data' => true,
            'timeline' => $timeline,
            'summary' => $summary,
            'totals' => [
                'total_expected_income' => round($totalIncome, 2),
                'total_expected_expense' => round($totalExpense, 2),
                'monthly_expense_average' => round($monthlyExpense, 2),
            ],
            'period_days' => $days,
        ];
    }

    /**
     * Get unpaid invoices grouped by due date, keyed by date string.
     * Overdue and no-due-date invoices are placed at today (day 0).
     */
    protected function getUnpaidInvoicesByDay(int $days): array
    {
        $today = now()->startOfDay();
        $endDate = now()->addDays($days)->endOfDay();

        // Overdue invoices (due_at < today) + invoices without due_at → counted at day 0
        $overdueTotal = (float) Invoice::unpaid()
            ->invoicesOnly()
            ->where(function ($q) use ($today) {
                $q->where('due_at', '<', $today)
                  ->orWhereNull('due_at');
            })
            ->sum('total_ttc');

        // Future invoices within the forecast window
        $futureIncome = Invoice::unpaid()
            ->invoicesOnly()
            ->whereNotNull('due_at')
            ->where('due_at', '>=', $today)
            ->where('due_at', '<=', $endDate)
            ->selectRaw('due_at, SUM(total_ttc) as total_due')
            ->groupBy('due_at')
            ->orderBy('due_at')
            ->get();

        $incomeByDay = [];

        if ($overdueTotal > 0) {
            $incomeByDay[$today->format('Y-m-d')] = $overdueTotal;
        }

        foreach ($futureIncome as $row) {
            $dateKey = Carbon::parse($row->due_at)->format('Y-m-d');
            $incomeByDay[$dateKey] = (float) ($incomeByDay[$dateKey] ?? 0) + (float) $row->total_due;
        }

        return $incomeByDay;
    }

    /**
     * Calculate the average monthly expense (TTC) over the last 6 months.
     */
    protected function getAverageMonthlyExpense(): float
    {
        $sixMonthsAgo = now()->subMonths(6)->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $totalExpenses = (float) Expense::dateBetween(
            $sixMonthsAgo->format('Y-m-d'),
            $lastMonthEnd->format('Y-m-d')
        )->sum('amount_ttc');

        if ($totalExpenses <= 0) {
            return 0;
        }

        return (float) bcdiv((string) $totalExpenses, '6', 4);
    }

    /**
     * Build the day-by-day timeline with cumulative values.
     */
    protected function buildDailyTimeline(int $days, array $incomeByDay, float $dailyExpense): array
    {
        $timeline = [];
        $cumulativeIncome = 0;
        $cumulativeExpense = 0;

        for ($i = 0; $i <= $days; $i++) {
            $date = now()->addDays($i);
            $dateKey = $date->format('Y-m-d');
            $incomeToday = $incomeByDay[$dateKey] ?? 0;

            $cumulativeIncome = (float) bcadd((string) $cumulativeIncome, (string) $incomeToday, 4);
            $cumulativeExpense = (float) bcadd((string) $cumulativeExpense, (string) $dailyExpense, 4);
            $netCash = (float) bcsub((string) $cumulativeIncome, (string) $cumulativeExpense, 4);

            $timeline[] = [
                'date' => $dateKey,
                'label' => $date->format('d/m'),
                'day_number' => $i,
                'income' => round($incomeToday, 2),
                'expense' => round($dailyExpense, 2),
                'cumulative_income' => round($cumulativeIncome, 2),
                'cumulative_expense' => round($cumulativeExpense, 2),
                'net_cash' => round($netCash, 2),
            ];
        }

        return $timeline;
    }

    /**
     * Extract summary values at key milestones (30, 60, 90 days).
     */
    protected function getSummaryAtMilestones(array $timeline): array
    {
        $get = fn (int $day) => isset($timeline[$day]) ? $timeline[$day]['net_cash'] : null;

        return [
            'current' => $get(0) ?? 0,
            'days_30' => $get(30),
            'days_60' => $get(60),
            'days_90' => $get(90),
        ];
    }
}
