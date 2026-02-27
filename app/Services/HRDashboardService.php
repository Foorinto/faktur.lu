<?php

namespace App\Services;

use App\Models\HR\Employee;
use App\Models\HR\LeaveRequest;
use Carbon\Carbon;

class HRDashboardService
{
    public function getWidgets(): array
    {
        $now = Carbon::now();

        return [
            'total_employees' => Employee::active()->count(),
            'entries_this_month' => Employee::whereMonth('contract_start', $now->month)
                ->whereYear('contract_start', $now->year)
                ->count(),
            'exits_this_month' => Employee::whereMonth('termination_date', $now->month)
                ->whereYear('termination_date', $now->year)
                ->count(),
            'absences_today' => LeaveRequest::approved()
                ->where('start_date', '<=', $now->toDateString())
                ->where('end_date', '>=', $now->toDateString())
                ->count(),
            'pending_requests' => LeaveRequest::pending()->count(),
            'upcoming_birthdays' => $this->getUpcomingBirthdays(),
            'trial_periods_ending' => $this->getTrialPeriodsEnding(),
            'contracts_to_renew' => $this->getContractsToRenew(),
        ];
    }

    private function getUpcomingBirthdays(int $days = 30): array
    {
        $today = Carbon::today();
        $end = $today->copy()->addDays($days);

        return Employee::active()
            ->whereNotNull('birth_date')
            ->get(['id', 'first_name', 'last_name', 'birth_date', 'photo_path'])
            ->filter(function ($employee) use ($today, $end) {
                $birthday = $employee->birth_date->copy()->year($today->year);
                if ($birthday->lt($today)) {
                    $birthday->addYear();
                }
                return $birthday->between($today, $end);
            })
            ->sortBy(function ($employee) use ($today) {
                $birthday = $employee->birth_date->copy()->year($today->year);
                if ($birthday->lt($today)) {
                    $birthday->addYear();
                }
                return $birthday;
            })
            ->values()
            ->take(5)
            ->map(fn ($e) => [
                'id' => $e->id,
                'full_name' => $e->full_name,
                'birth_date' => $e->birth_date->format('d/m'),
            ])
            ->toArray();
    }

    private function getTrialPeriodsEnding(int $days = 30): array
    {
        return Employee::active()
            ->whereNotNull('trial_end_date')
            ->whereBetween('trial_end_date', [Carbon::today(), Carbon::today()->addDays($days)])
            ->orderBy('trial_end_date')
            ->get(['id', 'first_name', 'last_name', 'trial_end_date'])
            ->map(fn ($e) => [
                'id' => $e->id,
                'full_name' => $e->full_name,
                'trial_end_date' => $e->trial_end_date->format('d/m/Y'),
                'days_left' => $e->trial_end_date->diffInDays(Carbon::today()),
            ])
            ->toArray();
    }

    private function getContractsToRenew(int $days = 60): array
    {
        return Employee::active()
            ->where('contract_type', 'CDD')
            ->whereNotNull('contract_end')
            ->whereBetween('contract_end', [Carbon::today(), Carbon::today()->addDays($days)])
            ->orderBy('contract_end')
            ->get(['id', 'first_name', 'last_name', 'contract_end'])
            ->map(fn ($e) => [
                'id' => $e->id,
                'full_name' => $e->full_name,
                'contract_end' => $e->contract_end->format('d/m/Y'),
                'days_left' => $e->contract_end->diffInDays(Carbon::today()),
            ])
            ->toArray();
    }
}
