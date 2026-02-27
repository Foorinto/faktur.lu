<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\LeaveBalance;
use App\Models\HR\LeaveRequest;
use App\Models\HR\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $query = LeaveRequest::query()
            ->with(['employee:id,first_name,last_name,photo_path', 'leaveType:id,name,color'])
            ->ofStatus($request->input('status'))
            ->when($request->input('employee'), fn ($q, $id) => $q->forEmployee((int) $id))
            ->latest();

        $leaveRequests = $query->paginate(15)->withQueryString();

        $statusCounts = LeaveRequest::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $year = now()->year;
        $employees = Employee::orderBy('last_name')
            ->with(['leaveBalances' => fn ($q) => $q->where('year', $year)])
            ->get(['id', 'first_name', 'last_name']);
        $leaveTypes = LeaveType::query()->orderBy('name')->get(['id', 'name', 'color', 'default_days_per_year']);

        // Jours en attente (pending) par employé par type de congé
        $pendingDays = LeaveRequest::where('status', 'pending')
            ->whereYear('start_date', $year)
            ->selectRaw('employee_id, leave_type_id, SUM(days_count) as total')
            ->groupBy('employee_id', 'leave_type_id')
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($items) => $items->pluck('total', 'leave_type_id'));

        return Inertia::render('HR/Leaves/Index', [
            'leaveRequests' => $leaveRequests,
            'filters' => [
                'status' => $request->input('status'),
                'employee' => $request->input('employee'),
            ],
            'statusCounts' => $statusCounts,
            'employees' => $employees,
            'leaveTypes' => $leaveTypes,
            'pendingDays' => $pendingDays,
        ]);
    }

    public function calendar(Request $request): Response
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
        $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');

        $leaveRequests = LeaveRequest::query()
            ->with(['employee:id,first_name,last_name,photo_path', 'leaveType:id,name,color'])
            ->whereIn('status', ['approved', 'pending'])
            ->whereDate('start_date', '<=', $endOfMonth)
            ->whereDate('end_date', '>=', $startOfMonth)
            ->get();

        return Inertia::render('HR/Leaves/Calendar', [
            'leaveRequests' => $leaveRequests,
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'days_count' => ['required', 'numeric', 'min:0.5'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        // Check for overlapping requests
        $overlap = LeaveRequest::forEmployee($validated['employee_id'])
            ->overlapping($validated['start_date'], $validated['end_date'])
            ->exists();

        if ($overlap) {
            return back()->with('error', 'Chevauchement détecté avec un congé existant.');
        }

        // Check remaining balance (used + pending)
        $year = Carbon::parse($validated['start_date'])->year;
        $leaveType = LeaveType::find($validated['leave_type_id']);
        $balance = LeaveBalance::where('employee_id', $validated['employee_id'])
            ->where('leave_type_id', $validated['leave_type_id'])
            ->where('year', $year)
            ->first();

        $initialDays = $balance ? $balance->initial_days : ($leaveType->default_days_per_year ?? 0);
        $usedDays = $balance ? $balance->used_days : 0;

        $pendingDays = LeaveRequest::forEmployee($validated['employee_id'])
            ->where('leave_type_id', $validated['leave_type_id'])
            ->whereYear('start_date', $year)
            ->where('status', 'pending')
            ->sum('days_count');

        $availableDays = round($initialDays - $usedDays - $pendingDays, 1);

        if ($validated['days_count'] > $availableDays) {
            return back()->withErrors([
                'days_count' => "Le nombre maximum de jours ouvrés disponibles est de {$availableDays} jours.",
            ]);
        }

        LeaveRequest::create($validated);

        return back()->with('success', 'Demande de congé créée.');
    }

    public function update(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        if (!in_array($leaveRequest->status, ['pending', 'rejected'])) {
            return back()->with('error', 'Seules les demandes en attente ou refusées peuvent être modifiées.');
        }

        $validated = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'days_count' => ['required', 'numeric', 'min:0.5'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        // Check for overlapping requests (exclude current)
        $overlap = LeaveRequest::forEmployee($leaveRequest->employee_id)
            ->overlapping($validated['start_date'], $validated['end_date'], $leaveRequest->id)
            ->exists();

        if ($overlap) {
            return back()->with('error', 'Chevauchement détecté avec un congé existant.');
        }

        // Check remaining balance (used + pending, excluding current request)
        $year = Carbon::parse($validated['start_date'])->year;
        $leaveType = LeaveType::find($validated['leave_type_id']);
        $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
            ->where('leave_type_id', $validated['leave_type_id'])
            ->where('year', $year)
            ->first();

        $initialDays = $balance ? $balance->initial_days : ($leaveType->default_days_per_year ?? 0);
        $usedDays = $balance ? $balance->used_days : 0;

        $pendingDays = LeaveRequest::forEmployee($leaveRequest->employee_id)
            ->where('leave_type_id', $validated['leave_type_id'])
            ->whereYear('start_date', $year)
            ->where('status', 'pending')
            ->where('id', '!=', $leaveRequest->id)
            ->sum('days_count');

        $availableDays = round($initialDays - $usedDays - $pendingDays, 1);

        if ($validated['days_count'] > $availableDays) {
            return back()->withErrors([
                'days_count' => "Le nombre maximum de jours ouvrés disponibles est de {$availableDays} jours.",
            ]);
        }

        // If rejected, reset to pending (re-submission)
        if ($leaveRequest->status === 'rejected') {
            $validated['status'] = 'pending';
            $validated['rejection_reason'] = null;
        }

        $leaveRequest->update($validated);

        return back()->with('success', 'Demande de congé mise à jour.');
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        if (!$leaveRequest->isPending()) {
            return back()->with('error', 'Cette demande ne peut plus être approuvée.');
        }

        $leaveRequest->approve();

        return back()->with('success', 'Demande de congé approuvée.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        if (!$leaveRequest->isPending()) {
            return back()->with('error', 'Cette demande ne peut plus être refusée.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $leaveRequest->reject($validated['rejection_reason']);

        return back()->with('success', 'Demande de congé refusée.');
    }

    public function reactivate(LeaveRequest $leaveRequest): RedirectResponse
    {
        if ($leaveRequest->status !== 'cancelled') {
            return back()->with('error', 'Seuls les congés annulés peuvent être réactivés.');
        }

        // Check for overlapping requests
        $overlap = LeaveRequest::forEmployee($leaveRequest->employee_id)
            ->overlapping($leaveRequest->start_date, $leaveRequest->end_date, $leaveRequest->id)
            ->exists();

        if ($overlap) {
            return back()->with('error', 'Chevauchement détecté avec un congé existant.');
        }

        $leaveRequest->reactivate();

        return back()->with('success', 'Demande de congé réactivée.');
    }

    public function cancel(LeaveRequest $leaveRequest): RedirectResponse
    {
        $leaveRequest->cancel();

        return back()->with('success', 'Demande de congé annulée.');
    }

    public function destroy(LeaveRequest $leaveRequest): RedirectResponse
    {
        if (!$leaveRequest->isPending()) {
            return back()->with('error', 'Seules les demandes en attente peuvent être supprimées.');
        }

        $leaveRequest->delete();

        return back()->with('success', 'Demande de congé supprimée.');
    }
}
