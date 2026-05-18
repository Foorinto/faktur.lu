<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\BusinessSettings;
use App\Models\HR\Employee;
use App\Models\HR\HrEvent;
use App\Models\HR\LeaveRequest;
use App\Models\HR\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SharedCalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $settings = BusinessSettings::getInstance();

        $employees = Employee::active()
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'photo_path', 'department_id', 'hide_leaves_from_team']);

        $rooms = Room::active()
            ->orderBy('name')
            ->get(['id', 'name', 'capacity', 'location']);

        return Inertia::render('HR/SharedCalendar', [
            'employees' => $employees,
            'rooms' => $rooms,
            'sharedCalendarEnabled' => $settings?->shared_calendar_enabled ?? true,
            'currentEmployeeId' => $request->user()?->linkedEmployee?->id,
            'isOrganizationOwner' => $request->user()?->isOrganizationOwner() ?? false,
        ]);
    }

    /**
     * JSON endpoint for FullCalendar: returns events + approved leaves in a date range.
     */
    public function events(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
            'employees' => ['nullable', 'array'],
            'employees.*' => ['integer'],
            'types' => ['nullable', 'array'],
            'types.*' => ['string'],
        ]);

        $start = Carbon::parse($validated['start']);
        $end = Carbon::parse($validated['end']);
        $employeeFilter = $validated['employees'] ?? null;
        $typeFilter = $validated['types'] ?? null;

        $isAdmin = $request->user()?->isOrganizationOwner() ?? false;

        // 1. Events
        $events = HrEvent::query()
            ->with(['room:id,name', 'participants:id,hr_event_id,employee_id', 'creator:id,first_name,last_name'])
            ->inRange($start, $end)
            ->when($typeFilter, fn ($q) => $q->whereIn('type', $typeFilter))
            ->get()
            ->map(fn (HrEvent $e) => [
                'id' => 'evt-'.$e->id,
                'kind' => 'event',
                'event_id' => $e->id,
                'title' => $e->title,
                'type' => $e->type,
                'color' => $e->effective_color,
                'start' => $e->starts_at->toIso8601String(),
                'end' => $e->ends_at->toIso8601String(),
                'allDay' => $e->is_all_day,
                'extendedProps' => [
                    'room' => $e->room?->name,
                    'location_type' => $e->location_type,
                    'address' => $e->address,
                    'video_url' => $e->video_url,
                    'description' => $e->description,
                    'creator' => $e->creator ? trim($e->creator->first_name.' '.$e->creator->last_name) : null,
                    'participants_count' => $e->participants->count(),
                ],
            ]);

        // 2. Approved + pending leaves (alimentés depuis LeaveRequest)
        $leavesQuery = LeaveRequest::query()
            ->with(['employee:id,first_name,last_name,hide_leaves_from_team', 'leaveType:id,name,color'])
            ->whereIn('status', ['approved', 'pending'])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start);

        if ($employeeFilter) {
            $leavesQuery->whereIn('employee_id', $employeeFilter);
        }

        $leaves = $leavesQuery->get()
            ->filter(fn (LeaveRequest $lr) => $isAdmin || ! ($lr->employee?->hide_leaves_from_team ?? false))
            ->map(function (LeaveRequest $lr) use ($isAdmin) {
                $endDate = Carbon::parse($lr->end_date)->addDay(); // FullCalendar exclusive
                $isPending = $lr->status === 'pending';
                $statusLabel = $isPending ? __('app.hr.leave_status_pending') : __('app.hr.leave_status_approved');

                $name = $lr->employee
                    ? trim($lr->employee->first_name.' '.($lr->employee->last_name ? substr($lr->employee->last_name, 0, 1).'.' : ''))
                    : '';

                $typeName = $lr->leaveType?->name ?? '';

                return [
                    'id' => 'lv-'.$lr->id,
                    'kind' => 'leave',
                    'leave_id' => $lr->id,
                    'title' => trim($name.' — '.$typeName.' ('.$statusLabel.')'),
                    'color' => $lr->leaveType?->color ?? '#94a3b8',
                    'classNames' => $isPending ? ['fc-event-pending'] : [],
                    'start' => $lr->start_date,
                    'end' => $endDate->format('Y-m-d'),
                    'allDay' => true,
                    'extendedProps' => [
                        'employee_id' => $lr->employee_id,
                        'employee_name' => $lr->employee ? trim($lr->employee->first_name.' '.$lr->employee->last_name) : null,
                        'leave_type' => $lr->leaveType?->name,
                        'status' => $lr->status,
                        'status_label' => $statusLabel,
                        'days_count' => $lr->days_count,
                        'start_date' => $lr->start_date,
                        'end_date' => $lr->end_date,
                        'reason' => $isAdmin ? $lr->reason : null,
                    ],
                ];
            })
            ->values();

        return response()->json([
            'events' => $events,
            'leaves' => $leaves,
        ]);
    }
}
