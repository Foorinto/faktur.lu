<?php

namespace App\Http\Controllers\HR;

use App\Actions\HR\CreateHrEventAction;
use App\Actions\HR\UpdateHrEventAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreHrEventRequest;
use App\Models\HR\Employee;
use App\Models\HR\HrEvent;
use App\Models\HR\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HrEventController extends Controller
{
    public function create(Request $request): Response
    {
        $this->authorize('create', HrEvent::class);

        return Inertia::render('HR/Events/Create', [
            'rooms' => Room::active()->orderBy('name')->get(['id', 'name', 'capacity', 'location']),
            'employees' => Employee::active()->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'photo_path']),
            'types' => HrEvent::TYPES,
            'currentEmployeeId' => $request->user()?->linkedEmployee?->id,
        ]);
    }

    public function store(StoreHrEventRequest $request, CreateHrEventAction $action): RedirectResponse
    {
        $this->authorize('create', HrEvent::class);

        $creator = $request->user()->linkedEmployee
            ?? Employee::where('user_id', $request->user()->id)->active()->first();

        if (! $creator) {
            return back()->with('error', __('app.hr_event_no_linked_employee'));
        }

        $action->execute($request->validated(), $request->user(), $creator);

        return redirect()
            ->route('hr.shared-calendar.index')
            ->with('success', __('app.hr_event_created'));
    }

    public function show(HrEvent $event): Response
    {
        $this->authorize('view', $event);

        return Inertia::render('HR/Events/Show', [
            'event' => $event->load(['room', 'creator:id,first_name,last_name', 'participants.employee:id,first_name,last_name,photo_path']),
        ]);
    }

    public function edit(Request $request, HrEvent $event): Response
    {
        $this->authorize('update', $event);

        return Inertia::render('HR/Events/Edit', [
            'event' => $event->load(['room', 'participants']),
            'rooms' => Room::active()->orderBy('name')->get(['id', 'name', 'capacity', 'location']),
            'employees' => Employee::active()->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'photo_path']),
            'types' => HrEvent::TYPES,
        ]);
    }

    public function update(StoreHrEventRequest $request, HrEvent $event, UpdateHrEventAction $action): RedirectResponse
    {
        $this->authorize('update', $event);

        $action->execute($event, $request->validated());

        return redirect()
            ->route('hr.events.show', $event)
            ->with('success', __('app.hr_event_updated'));
    }

    public function destroy(HrEvent $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()
            ->route('hr.shared-calendar.index')
            ->with('success', __('app.hr_event_deleted'));
    }
}
