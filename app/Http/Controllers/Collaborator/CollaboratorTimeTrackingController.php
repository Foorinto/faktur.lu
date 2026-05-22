<?php

namespace App\Http\Controllers\Collaborator;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CollaboratorTimeTrackingController extends Controller
{
    private function getOrganization(Request $request)
    {
        $organization = $request->user()->getOrganization();
        abort_unless($organization, 403);

        return $organization;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $organization = $this->getOrganization($request);
        $ownerId = $organization->user_id;

        $request->validate([
            'period' => 'nullable|string|in:today,week,month',
        ]);

        $period = $request->input('period', 'week');

        $startDate = match ($period) {
            'today' => Carbon::today(),
            'month' => Carbon::now()->startOfMonth(),
            default => Carbon::now()->startOfWeek(),
        };

        $timeEntries = TimeEntry::where('user_id', $user->id)
            ->where('started_at', '>=', $startDate)
            ->with('project')
            ->orderBy('started_at', 'desc')
            ->get()
            ->map(fn ($te) => [
                'id' => $te->id,
                'description' => $te->description,
                'project_name' => $te->project?->title ?? $te->project_name,
                'project_id' => $te->project_id,
                'started_at' => $te->started_at?->toISOString(),
                'stopped_at' => $te->stopped_at?->toISOString(),
                'duration_seconds' => $te->duration_seconds,
                'duration_formatted' => $te->duration_formatted,
                'is_running' => $te->isRunning(),
            ]);

        // Available projects for timer
        $projects = Project::withoutGlobalScopes()
            ->accessibleByCollaborator($request->user()->id)
            ->active()
            ->orderBy('title')
            ->with(['tasks' => fn ($q) => $q->withoutGlobalScopes()->select('id', 'project_id', 'title', 'parent_id')->where('is_completed', false)->whereNull('parent_id')->orderBy('sort_order')])
            ->get(['id', 'title', 'color']);

        // Running timer
        $runningTimer = TimeEntry::where('user_id', $user->id)
            ->running()
            ->with('project')
            ->first();

        // Total hours for period
        $totalSeconds = TimeEntry::where('user_id', $user->id)
            ->where('started_at', '>=', $startDate)
            ->whereNotNull('stopped_at')
            ->sum('duration_seconds');

        return Inertia::render('Collaborator/TimeTracking/Index', [
            'timeEntries' => $timeEntries,
            'projects' => $projects,
            'runningTimer' => $runningTimer ? [
                'id' => $runningTimer->id,
                'description' => $runningTimer->description,
                'project_name' => $runningTimer->project?->title,
                'project_id' => $runningTimer->project_id,
                'started_at' => $runningTimer->started_at->toISOString(),
            ] : null,
            'totalHours' => round($totalSeconds / 3600, 1),
            'period' => $period,
        ]);
    }

    public function store(Request $request)
    {
        $organization = $this->getOrganization($request);

        $validated = $request->validate([
            'description' => 'nullable|string|max:500',
            'project_id' => 'nullable|integer',
            'task_id' => 'nullable|integer',
            'started_at' => 'required|date',
            'stopped_at' => 'required|date|after:started_at',
        ]);

        // Verify project belongs to org owner
        if (!empty($validated['project_id'])) {
            Project::withoutGlobalScopes()
                ->where('id', $validated['project_id'])
                ->accessibleByCollaborator($request->user()->id)
                ->firstOrFail();
        }

        $startedAt = Carbon::parse($validated['started_at']);
        $stoppedAt = Carbon::parse($validated['stopped_at']);

        TimeEntry::create([
            'user_id' => $request->user()->id,
            'project_id' => $validated['project_id'] ?? null,
            'task_id' => $validated['task_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'started_at' => $startedAt,
            'stopped_at' => $stoppedAt,
            'duration_seconds' => $stoppedAt->diffInSeconds($startedAt),
        ]);

        return back()->with('success', __('app.time_entry_created'));
    }

    public function update(Request $request, TimeEntry $timeEntry)
    {
        if ($timeEntry->user_id !== $request->user()->id) {
            abort(403);
        }

        $organization = $this->getOrganization($request);

        $validated = $request->validate([
            'description' => 'nullable|string|max:500',
            'project_id' => 'nullable|integer',
            'started_at' => 'sometimes|required|date',
            'stopped_at' => 'sometimes|required|date|after:started_at',
        ]);

        if (!empty($validated['project_id'])) {
            Project::withoutGlobalScopes()
                ->where('id', $validated['project_id'])
                ->accessibleByCollaborator($request->user()->id)
                ->firstOrFail();
        }

        $timeEntry->update($validated);

        return back()->with('success', __('app.time_entry_updated'));
    }

    public function destroy(Request $request, TimeEntry $timeEntry)
    {
        if ($timeEntry->user_id !== $request->user()->id) {
            abort(403);
        }

        $timeEntry->delete();

        return back()->with('success', __('app.time_entry_deleted'));
    }

    public function start(Request $request)
    {
        $user = $request->user();
        $organization = $this->getOrganization($request);

        $validated = $request->validate([
            'description' => 'nullable|string|max:500',
            'project_id' => 'nullable|integer',
            'task_id' => 'nullable|integer',
        ]);

        // Stop any running timer
        TimeEntry::where('user_id', $user->id)
            ->running()
            ->each(fn ($te) => $te->stop());

        if (!empty($validated['project_id'])) {
            Project::withoutGlobalScopes()
                ->where('id', $validated['project_id'])
                ->accessibleByCollaborator($request->user()->id)
                ->firstOrFail();
        }

        $entry = new TimeEntry();
        $entry->user_id = $user->id;
        $entry->project_id = $validated['project_id'] ?? null;
        $entry->task_id = $validated['task_id'] ?? null;
        $entry->description = $validated['description'] ?? null;
        $entry->start();

        return back()->with('success', __('app.timer_started'));
    }

    public function stop(Request $request, TimeEntry $timeEntry)
    {
        if ($timeEntry->user_id !== $request->user()->id) {
            abort(403);
        }

        $timeEntry->stop();

        return back()->with('success', __('app.timer_stopped'));
    }
}
