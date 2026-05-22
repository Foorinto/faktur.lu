<?php

namespace App\Http\Controllers\Collaborator;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CollaboratorDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $organization = $user->getOrganization();

        if (!$organization) {
            abort(403);
        }

        $ownerId = $organization->user_id;

        // Active projects visible to collaborator
        $projects = Project::withoutGlobalScopes()
            ->accessibleByCollaborator($user->id)
            ->active()
            ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->where('is_completed', true)])
            ->orderBy('updated_at', 'desc')
            ->take(6)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'status' => $p->status,
                'status_label' => $p->status_labels[$p->status] ?? $p->status,
                'color' => $p->color,
                'tasks_count' => $p->tasks_count,
                'completed_tasks_count' => $p->completed_tasks_count,
                'due_date' => $p->due_date?->format('d/m/Y'),
            ]);

        // Running timer
        $runningTimer = TimeEntry::where('user_id', $user->id)
            ->running()
            ->with('project')
            ->first();

        // Hours this week
        $weekStart = Carbon::now()->startOfWeek();
        $hoursThisWeek = TimeEntry::where('user_id', $user->id)
            ->where('started_at', '>=', $weekStart)
            ->whereNotNull('stopped_at')
            ->sum('duration_seconds');

        // Hours today
        $hoursToday = TimeEntry::where('user_id', $user->id)
            ->where('started_at', '>=', Carbon::today())
            ->whereNotNull('stopped_at')
            ->sum('duration_seconds');

        return Inertia::render('Collaborator/Dashboard', [
            'organization' => [
                'name' => $organization->name,
            ],
            'projects' => $projects,
            'stats' => [
                'active_projects' => Project::withoutGlobalScopes()
                    ->accessibleByCollaborator($user->id)
                    ->active()
                    ->count(),
                'hours_this_week' => round($hoursThisWeek / 3600, 1),
                'hours_today' => round($hoursToday / 3600, 1),
            ],
            'runningTimer' => $runningTimer ? [
                'id' => $runningTimer->id,
                'description' => $runningTimer->description,
                'project_name' => $runningTimer->project?->title ?? $runningTimer->project_name,
                'started_at' => $runningTimer->started_at->toISOString(),
            ] : null,
        ]);
    }
}
