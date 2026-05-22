<?php

namespace App\Http\Controllers\Collaborator;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CollaboratorProjectController extends Controller
{
    private function getOrganizationOwnerId(Request $request): int
    {
        $organization = $request->user()->getOrganization();
        abort_unless($organization, 403);

        return $organization->user_id;
    }

    private function findProject(int $projectId, int $ownerId, int $userId): Project
    {
        return Project::withoutGlobalScopes()
            ->where('id', $projectId)
            ->accessibleByCollaborator($userId)
            ->firstOrFail();
    }

    public function index(Request $request)
    {
        $ownerId = $this->getOrganizationOwnerId($request);

        $request->validate([
            'status' => 'nullable|string',
            'search' => 'nullable|string|max:100',
        ]);

        $query = Project::withoutGlobalScopes()
            ->accessibleByCollaborator($request->user()->id)
            ->active()
            ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->where('is_completed', true)])
            ->with('client:id,name');

        if ($request->filled('status')) {
            $query->status($request->status);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $projects = $query->orderBy('sort_order')->orderBy('title')->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'description' => $p->description,
                'status' => $p->status,
                'status_label' => $p->status_labels[$p->status] ?? $p->status,
                'color' => $p->color,
                'due_date' => $p->due_date?->format('Y-m-d'),
                'budget_hours' => $p->budget_hours,
                'tasks_count' => $p->tasks_count,
                'completed_tasks_count' => $p->completed_tasks_count,
                'total_time_spent' => $p->total_time_spent_formatted,
                'client_name' => $p->client?->name,
                'created_by' => $p->created_by,
                'is_mine' => $p->created_by === $request->user()->id,
            ]);

        return Inertia::render('Collaborator/Projects/Index', [
            'projects' => $projects,
            'filters' => $request->only(['status', 'search']),
            'statuses' => Project::STATUSES,
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Collaborator/Projects/Create', [
            'statuses' => Project::STATUSES,
            'colors' => Project::COLORS,
        ]);
    }

    public function store(Request $request)
    {
        $ownerId = $this->getOrganizationOwnerId($request);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:' . implode(',', array_keys(Project::STATUSES)),
            'color' => 'nullable|string|max:7',
            'due_date' => 'nullable|date',
            'budget_hours' => 'nullable|numeric|min:0',
        ]);

        $project = Project::withoutGlobalScopes()->create([
            'user_id' => $ownerId,
            'created_by' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? Project::STATUS_BACKLOG,
            'color' => $validated['color'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'budget_hours' => $validated['budget_hours'] ?? null,
        ]);

        // FEAT-081: the creator must be a project_member to retain access via accessibleByCollaborator().
        \DB::table('project_members')->insertOrIgnore([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'member_type' => 'collaborator',
            'invited_email' => $request->user()->email,
            'invited_at' => now(),
            'accepted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Auto-attach active employees of the org (same behaviour as admin Project creation).
        app(\App\Actions\Project\AutoAttachEmployeesToProjectAction::class)->execute($project);

        return redirect()->route('collaborator.projects.index')
            ->with('success', __('app.projects_flash.created'));
    }

    public function show(Request $request, int $project)
    {
        $ownerId = $this->getOrganizationOwnerId($request);
        $project = $this->findProject($project, $ownerId, $request->user()->id);

        $project->load([
            'tasks' => fn ($q) => $q->rootTasks()->orderBy('sort_order'),
            'tasks.children' => fn ($q) => $q->orderBy('sort_order'),
            'tasks.assignedEmployees' => fn ($q) => $q->withoutGlobalScope('user')->select(['employees.id', 'first_name', 'last_name']),
            'tasks.assignedUsers:id,name',
            'tasks.children.assignedEmployees' => fn ($q) => $q->withoutGlobalScope('user')->select(['employees.id', 'first_name', 'last_name']),
            'tasks.children.assignedUsers:id,name',
            'client:id,name',
        ]);

        // Get time entries for this project by the current collaborator
        $timeEntries = $project->timeEntries()
            ->where('user_id', $request->user()->id)
            ->orderBy('started_at', 'desc')
            ->take(20)
            ->get()
            ->map(fn ($te) => [
                'id' => $te->id,
                'description' => $te->description,
                'started_at' => $te->started_at?->toISOString(),
                'stopped_at' => $te->stopped_at?->toISOString(),
                'duration_formatted' => $te->duration_formatted,
                'is_running' => $te->isRunning(),
            ]);

        return Inertia::render('Collaborator/Projects/Show', [
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
                'description' => $project->description,
                'status' => $project->status,
                'status_label' => $project->status_labels[$project->status] ?? $project->status,
                'color' => $project->color,
                'due_date' => $project->due_date?->format('Y-m-d'),
                'budget_hours' => $project->budget_hours,
                'total_time_spent' => $project->total_time_spent_formatted,
                'completion_percentage' => $project->completion_percentage,
                'client_name' => $project->client?->name,
                'created_by' => $project->created_by,
                'is_mine' => $project->created_by === $request->user()->id,
                'tasks' => $project->tasks->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'description' => $t->description,
                    'status' => $t->status,
                    'priority' => $t->priority,
                    'is_completed' => $t->is_completed,
                    'due_date' => $t->due_date?->format('Y-m-d'),
                    'sort_order' => $t->sort_order,
                    'assigned_employees' => $t->assignedEmployees->map(fn ($e) => [
                        'id' => $e->id,
                        'first_name' => $e->first_name,
                        'last_name' => $e->last_name,
                    ]),
                    'assigned_users' => $t->assignedUsers->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                    ]),
                    'children' => $t->children->map(fn ($c) => [
                        'id' => $c->id,
                        'title' => $c->title,
                        'status' => $c->status,
                        'priority' => $c->priority,
                        'is_completed' => $c->is_completed,
                        'due_date' => $c->due_date?->format('Y-m-d'),
                        'sort_order' => $c->sort_order,
                        'assigned_employees' => $c->assignedEmployees->map(fn ($e) => [
                            'id' => $e->id,
                            'first_name' => $e->first_name,
                            'last_name' => $e->last_name,
                        ]),
                        'assigned_users' => $c->assignedUsers->map(fn ($u) => [
                            'id' => $u->id,
                            'name' => $u->name,
                        ]),
                    ]),
                ]),
            ],
            'timeEntries' => $timeEntries,
            'statuses' => Project::STATUSES,
            'taskStatuses' => Task::STATUSES,
            'taskPriorities' => Task::PRIORITIES,
        ]);
    }

    public function update(Request $request, int $project)
    {
        $ownerId = $this->getOrganizationOwnerId($request);
        $project = $this->findProject($project, $ownerId, $request->user()->id);

        // Only creator or org admin can update
        if ($project->created_by !== $request->user()->id) {
            abort(403, __('app.collaborator_flash.error_project_not_owner_update'));
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:' . implode(',', array_keys(Project::STATUSES)),
            'color' => 'nullable|string|max:7',
            'due_date' => 'nullable|date',
            'budget_hours' => 'nullable|numeric|min:0',
        ]);

        $project->update($validated);

        return back()->with('success', __('app.projects_flash.updated'));
    }

    public function destroy(Request $request, int $project)
    {
        $ownerId = $this->getOrganizationOwnerId($request);
        $project = $this->findProject($project, $ownerId, $request->user()->id);

        if ($project->created_by !== $request->user()->id) {
            abort(403, __('app.collaborator_flash.error_project_not_owner_delete'));
        }

        if (!$project->canBeDeleted()) {
            return back()->withErrors(['project' => __('app.collaborator_flash.error_project_has_time_entries')]);
        }

        $project->delete();

        return redirect()->route('collaborator.projects.index')
            ->with('success', __('app.projects_flash.deleted'));
    }
}
