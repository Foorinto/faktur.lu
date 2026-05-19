<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EmployeePortal\Concerns\ResolvesEmployee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortalProjectController extends Controller
{
    use ResolvesEmployee;

    /**
     * List active projects the employee is assigned to.
     */
    public function index(): Response
    {
        $employee = $this->employee();

        $projects = $employee->activeProjects()
            ->withoutGlobalScope('user')
            ->with('client:id,name')
            ->where('projects.is_archived', false)
            ->orderBy('projects.title')
            ->get([
                'projects.id',
                'projects.title',
                'projects.description',
                'projects.status',
                'projects.color',
                'projects.client_id',
                'projects.due_date',
            ]);

        // Add per-project stats
        $projectIds = $projects->pluck('id')->all();

        // Total open tasks per project (employee sees all project tasks).
        $tasksCount = Task::query()
            ->withoutGlobalScope('user')
            ->whereIn('project_id', $projectIds)
            ->whereNotIn('status', ['done'])
            ->selectRaw('project_id, COUNT(*) as count')
            ->groupBy('project_id')
            ->pluck('count', 'project_id');

        $hoursThisMonth = TimeEntry::query()
            ->withoutGlobalScope('user')
            ->whereIn('project_id', $projectIds)
            ->where('employee_id', $employee->id)
            ->whereMonth('started_at', now()->month)
            ->whereYear('started_at', now()->year)
            ->selectRaw('project_id, SUM(duration_seconds) as total')
            ->groupBy('project_id')
            ->pluck('total', 'project_id');

        $projects->each(function ($p) use ($tasksCount, $hoursThisMonth) {
            $p->my_tasks_count = $tasksCount[$p->id] ?? 0;
            $p->my_hours_this_month = round(($hoursThisMonth[$p->id] ?? 0) / 3600, 1);
        });

        return Inertia::render('EmployeePortal/Projects/Index', [
            'projects' => $projects,
        ]);
    }

    /**
     * Show project detail (only if employee has active access).
     */
    public function show(int $projectId): Response
    {
        $employee = $this->employee();

        // Bypass global 'user' scope: the employee's auth()->id() is their account_id,
        // not the org owner's user_id (which is what Project.user_id points to).
        $project = Project::withoutGlobalScope('user')
            ->where('id', $projectId)
            ->where('user_id', $employee->user_id)
            ->firstOrFail();

        // Verify employee has active access on this project
        $hasAccess = $employee->activeProjects()
            ->withoutGlobalScope('user')
            ->where('projects.id', $project->id)
            ->exists();

        abort_unless($hasAccess, 403);

        $project->load(['client:id,name']);

        // All project tasks (employees see the full backlog; assignees are shown for transparency).
        $tasks = Task::query()
            ->withoutGlobalScope('user')
            ->where('project_id', $project->id)
            ->with([
                'assignedEmployees:id,first_name,last_name',
                'assignedUsers:id,name',
            ])
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 1 WHEN 'next' THEN 2 WHEN 'backlog' THEN 3 WHEN 'waiting_for' THEN 4 WHEN 'done' THEN 5 ELSE 6 END")
            ->get(['id', 'title', 'description', 'status', 'due_date']);

        // Employee's time entries on this project (last 30)
        $timeEntries = TimeEntry::query()
            ->withoutGlobalScope('user')
            ->where('project_id', $project->id)
            ->where('employee_id', $employee->id)
            ->orderByDesc('started_at')
            ->limit(30)
            ->get(['id', 'started_at', 'ended_at', 'duration_seconds', 'description']);

        return Inertia::render('EmployeePortal/Projects/Show', [
            'project' => $project,
            'tasks' => $tasks,
            'timeEntries' => $timeEntries,
        ]);
    }
}
