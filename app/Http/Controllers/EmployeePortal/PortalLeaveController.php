<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EmployeePortal\Concerns\ResolvesEmployee;
use App\Models\HR\LeaveBalance;
use App\Models\HR\LeaveRequest;
use App\Models\HR\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortalLeaveController extends Controller
{
    use ResolvesEmployee;

    public function index(): Response
    {
        $employee = $this->employee();
        $year = now()->year;

        $leaveRequests = LeaveRequest::withoutGlobalScope('user')
            ->where('employee_id', $employee->id)
            ->with(['leaveType' => fn ($q) => $q->withoutGlobalScope('user')])
            ->latest()
            ->paginate(15);

        $leaveBalances = LeaveBalance::withoutGlobalScope('user')
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->with(['leaveType' => fn ($q) => $q->withoutGlobalScope('user')])
            ->get();

        $leaveTypes = LeaveType::forUser($employee->user_id)
            ->orderBy('name')
            ->get(['id', 'name', 'color', 'default_days_per_year']);

        $pendingDays = LeaveRequest::withoutGlobalScope('user')
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->whereYear('start_date', $year)
            ->selectRaw('leave_type_id, SUM(days_count) as total')
            ->groupBy('leave_type_id')
            ->pluck('total', 'leave_type_id');

        return Inertia::render('EmployeePortal/Leaves/Index', [
            'leaveRequests' => $leaveRequests,
            'leaveBalances' => $leaveBalances,
            'leaveTypes' => $leaveTypes,
            'pendingDays' => $pendingDays,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $this->employee();

        $validated = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'days_count' => ['required', 'numeric', 'min:0.5'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        // Check for overlapping requests
        $overlap = LeaveRequest::withoutGlobalScope('user')
            ->where('employee_id', $employee->id)
            ->overlapping($validated['start_date'], $validated['end_date'])
            ->exists();

        if ($overlap) {
            return back()->with('error', __('app.hr.overlap_detected'));
        }

        // Check remaining balance
        $year = Carbon::parse($validated['start_date'])->year;
        $leaveType = LeaveType::forUser($employee->user_id)
            ->where('id', $validated['leave_type_id'])
            ->first();
        $balance = LeaveBalance::withoutGlobalScope('user')
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $validated['leave_type_id'])
            ->where('year', $year)
            ->first();

        $initialDays = $balance ? $balance->initial_days : ($leaveType->default_days_per_year ?? 0);
        $usedDays = $balance ? $balance->used_days : 0;

        $pendingDays = LeaveRequest::withoutGlobalScope('user')
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $validated['leave_type_id'])
            ->whereYear('start_date', $year)
            ->where('status', 'pending')
            ->sum('days_count');

        $availableDays = round($initialDays - $usedDays - $pendingDays, 1);

        if ($validated['days_count'] > $availableDays) {
            return back()->withErrors([
                'days_count' => __('app.employee_portal.insufficient_balance', ['days' => $availableDays]),
            ]);
        }

        LeaveRequest::withoutGlobalScope('user')->create([
            ...$validated,
            'employee_id' => $employee->id,
            'user_id' => $employee->user_id,
        ]);

        return back()->with('success', __('app.employee_portal.leave_request_created'));
    }

    public function cancel(int $id): RedirectResponse
    {
        $employee = $this->employee();
        $leaveRequest = LeaveRequest::withoutGlobalScope('user')->findOrFail($id);
        abort_unless((int) $leaveRequest->employee_id === (int) $employee->id, 403);

        if (!in_array($leaveRequest->status, ['pending', 'approved'])) {
            return back()->with('error', __('app.employee_portal.cannot_cancel'));
        }

        $leaveRequest->cancel();

        return back()->with('success', __('app.employee_portal.leave_request_cancelled'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $employee = $this->employee();
        $leaveRequest = LeaveRequest::withoutGlobalScope('user')->findOrFail($id);
        abort_unless((int) $leaveRequest->employee_id === (int) $employee->id, 403);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', __('app.employee_portal.cannot_delete'));
        }

        $leaveRequest->delete();

        return back()->with('success', __('app.employee_portal.leave_request_deleted'));
    }
}
