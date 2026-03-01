<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EmployeePortal\Concerns\ResolvesEmployee;
use App\Models\HR\ExpenseReport;
use App\Models\HR\LeaveRequest;
use Inertia\Inertia;
use Inertia\Response;

class PortalDashboardController extends Controller
{
    use ResolvesEmployee;

    public function index(): Response
    {
        $employee = $this->employee();
        $employee->load([
            'department:id,name,color',
            'leaveBalances' => fn ($q) => $q->withoutGlobalScope('user')
                ->where('year', now()->year)
                ->with(['leaveType' => fn ($q2) => $q2->withoutGlobalScope('user')]),
        ]);

        $pendingLeaves = LeaveRequest::withoutGlobalScope('user')
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        $pendingExpenses = ExpenseReport::withoutGlobalScope('user')
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        $recentDocuments = $employee->documents()
            ->withoutGlobalScope('user')
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('EmployeePortal/Dashboard', [
            'employee' => $employee,
            'pendingLeaves' => $pendingLeaves,
            'pendingExpenses' => $pendingExpenses,
            'recentDocuments' => $recentDocuments,
        ]);
    }
}
