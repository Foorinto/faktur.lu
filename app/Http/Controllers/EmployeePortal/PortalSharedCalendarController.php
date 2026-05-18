<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Actions\HR\CreateHrEventAction;
use App\Actions\HR\UpdateHrEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\EmployeePortal\Concerns\ResolvesEmployee;
use App\Http\Requests\HR\StoreHrEventRequest;
use App\Models\BusinessSettings;
use App\Models\HR\Employee;
use App\Models\HR\HrEvent;
use App\Models\HR\LeaveRequest;
use App\Models\HR\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortalSharedCalendarController extends Controller
{
    use ResolvesEmployee;

    public function index(): Response
    {
        $employee = $this->employee();
        $orgUserId = $employee->user_id;

        $settings = BusinessSettings::withoutGlobalScope('user')
            ->where('user_id', $orgUserId)
            ->first();

        $employees = Employee::withoutGlobalScope('user')
            ->where('user_id', $orgUserId)
            ->where('status', 'active')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'photo_path', 'department_id', 'hide_leaves_from_team']);

        $rooms = Room::withoutGlobalScope('user')
            ->where('user_id', $orgUserId)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'capacity', 'location']);

        return Inertia::render('EmployeePortal/SharedCalendar', [
            'employees' => $employees,
            'rooms' => $rooms,
            'sharedCalendarEnabled' => $settings?->shared_calendar_enabled ?? true,
            'currentEmployeeId' => $employee->id,
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        $employee = $this->employee();
        $orgUserId = $employee->user_id;

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
        $typeFilter = $validated['types'] ?? null;
        $employeeFilter = $validated['employees'] ?? null;

        // 1. Events of the organisation
        $events = HrEvent::withoutGlobalScope('user')
            ->where('user_id', $orgUserId)
            ->with(['room:id,name', 'creator:id,first_name,last_name'])
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
                    'creator' => $e->creator ? trim($e->creator->first_name.' '.$e->creator->last_name) : null,
                ],
            ]);

        // 2. Approved leaves of the organisation (filtered by hide_leaves_from_team)
        $leavesQuery = LeaveRequest::withoutGlobalScope('user')
            ->whereHas('employee', fn ($q) => $q->where('user_id', $orgUserId))
            ->with(['employee:id,first_name,last_name,hide_leaves_from_team,user_id', 'leaveType:id,name,color'])
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start);

        if ($employeeFilter) {
            $leavesQuery->whereIn('employee_id', $employeeFilter);
        }

        $leaves = $leavesQuery->get()
            // Always show own leaves; hide others if hide_leaves_from_team
            ->filter(fn (LeaveRequest $lr) =>
                $lr->employee_id === $employee->id
                || ! ($lr->employee?->hide_leaves_from_team ?? false)
            )
            ->map(function (LeaveRequest $lr) {
                $endDate = Carbon::parse($lr->end_date)->addDay();

                return [
                    'id' => 'lv-'.$lr->id,
                    'kind' => 'leave',
                    'title' => $lr->employee
                        ? trim($lr->employee->first_name.' '.($lr->employee->last_name ? substr($lr->employee->last_name, 0, 1).'.' : '')).' — '.($lr->leaveType?->name ?? '')
                        : ($lr->leaveType?->name ?? ''),
                    'color' => $lr->leaveType?->color ?? '#94a3b8',
                    'start' => $lr->start_date,
                    'end' => $endDate->format('Y-m-d'),
                    'allDay' => true,
                    'extendedProps' => [
                        'employee_id' => $lr->employee_id,
                        'leave_type' => $lr->leaveType?->name,
                        // No reason exposed in employee portal (privacy)
                    ],
                ];
            })
            ->values();

        return response()->json([
            'events' => $events,
            'leaves' => $leaves,
        ]);
    }

    // ─── Events CRUD (limited to creator) ─────────────────────────────────

    public function createEvent(): Response
    {
        $employee = $this->employee();
        $orgUserId = $employee->user_id;

        $rooms = Room::withoutGlobalScope('user')
            ->where('user_id', $orgUserId)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'capacity', 'location']);

        $employees = Employee::withoutGlobalScope('user')
            ->where('user_id', $orgUserId)
            ->where('status', 'active')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'photo_path']);

        return Inertia::render('EmployeePortal/Events/Create', [
            'rooms' => $rooms,
            'employees' => $employees,
            'types' => HrEvent::TYPES,
            'currentEmployeeId' => $employee->id,
        ]);
    }

    public function storeEvent(StoreHrEventRequest $request, CreateHrEventAction $action): RedirectResponse
    {
        $employee = $this->employee();
        $orgOwner = \App\Models\User::find($employee->user_id);

        if (! $orgOwner) {
            return back()->with('error', __('app.hr_event_no_linked_employee'));
        }

        $action->execute($request->validated(), $orgOwner, $employee);

        return redirect()
            ->route('employee-portal.shared-calendar.index')
            ->with('success', __('app.hr_event_created'));
    }

    public function showEvent(HrEvent $event): Response
    {
        $employee = $this->employee();
        abort_unless($event->user_id === $employee->user_id, 403);

        return Inertia::render('EmployeePortal/Events/Show', [
            'event' => $event->load(['room', 'creator:id,first_name,last_name', 'participants.employee:id,first_name,last_name,photo_path']),
            'canEdit' => $event->created_by_employee_id === $employee->id,
        ]);
    }

    public function editEvent(HrEvent $event): Response
    {
        $employee = $this->employee();
        abort_unless($event->user_id === $employee->user_id, 403);
        abort_unless($event->created_by_employee_id === $employee->id, 403);

        return Inertia::render('EmployeePortal/Events/Edit', [
            'event' => $event->load(['room', 'participants']),
            'rooms' => Room::withoutGlobalScope('user')
                ->where('user_id', $employee->user_id)
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'capacity', 'location']),
            'employees' => Employee::withoutGlobalScope('user')
                ->where('user_id', $employee->user_id)
                ->where('status', 'active')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'photo_path']),
            'types' => HrEvent::TYPES,
        ]);
    }

    public function updateEvent(StoreHrEventRequest $request, HrEvent $event, UpdateHrEventAction $action): RedirectResponse
    {
        $employee = $this->employee();
        abort_unless($event->user_id === $employee->user_id, 403);
        abort_unless($event->created_by_employee_id === $employee->id, 403);

        $action->execute($event, $request->validated());

        return redirect()
            ->route('employee-portal.shared-calendar.events.show', $event->id)
            ->with('success', __('app.hr_event_updated'));
    }

    public function destroyEvent(HrEvent $event): RedirectResponse
    {
        $employee = $this->employee();
        abort_unless($event->user_id === $employee->user_id, 403);
        abort_unless($event->created_by_employee_id === $employee->id, 403);

        $event->delete();

        return redirect()
            ->route('employee-portal.shared-calendar.index')
            ->with('success', __('app.hr_event_deleted'));
    }
}
