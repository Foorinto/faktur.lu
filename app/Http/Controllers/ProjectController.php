<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index(Request $request): Response
    {
        $query = Project::query()
            ->with(['client:id,name'])
            ->withCount(['tasks', 'tasks as completed_tasks_count' => function ($q) {
                $q->where('is_completed', true);
            }])
            ->withSum('timeEntries', 'duration_seconds');

        // Apply filters
        $query->status($request->get('status'))
            ->forClient($request->get('client_id'))
            ->search($request->get('search'));

        // Handle archived filter
        if ($request->boolean('show_archived')) {
            // Show all
        } else {
            $query->active();
        }

        // Get view type
        $view = $request->get('view', 'list');

        // Order by sort_order for Kanban, or by date for list
        if ($view === 'kanban') {
            $projects = $query->orderBy('sort_order')->get();
        } else {
            $projects = $query->latest()->paginate(20)->withQueryString();
        }

        // Stats
        $stats = [
            'total' => Project::active()->count(),
            'in_progress' => Project::active()->status('in_progress')->count(),
            'overdue' => Project::active()->overdue()->count(),
            'done_this_week' => Project::status('done')
                ->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
        ];

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'stats' => $stats,
            'view' => $view,
            'filters' => $request->only(['status', 'client_id', 'search', 'show_archived']),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'statuses' => Project::STATUSES,
            'colors' => Project::COLORS,
        ]);
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(): Response
    {
        return Inertia::render('Projects/Create', [
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'statuses' => Project::STATUSES,
            'colors' => Project::COLORS,
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'client_id' => 'nullable|exists:clients,id',
            'status' => 'required|in:' . implode(',', array_keys(Project::STATUSES)),
            'color' => 'nullable|string|max:7',
            'due_date' => 'nullable|date',
            'budget_hours' => 'nullable|numeric|min:0|max:9999',
        ]);

        // Verify client belongs to user if provided
        if (!empty($validated['client_id'])) {
            $client = Client::find($validated['client_id']);
            if (!$client || !$client->belongsToAuthUser()) {
                return back()->withErrors(['client_id' => 'Client invalide.']);
            }
        }

        $project = Project::create($validated);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Projet créé avec succès.');
    }

    /**
     * Display the specified project.
     */
    public function show(Request $request, Project $project): Response
    {
        $taskView = $request->get('task_view', 'list');
        $taskSort = $request->get('sort', 'manual');

        // Load tasks with children (sorting is done client-side for responsiveness)
        $tasks = $project->tasks()
            ->rootTasks()
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        $project->setRelation('tasks', $tasks);

        $project->load([
            'client:id,name',
            'timeEntries' => fn($q) => $q->with('task:id,title')->latest()->limit(10),
        ]);

        $project->loadCount(['tasks', 'tasks as completed_tasks_count' => function ($q) {
            $q->where('is_completed', true);
        }]);

        $project->loadSum('timeEntries', 'duration_seconds');

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'taskView' => $taskView,
            'taskSort' => $taskSort,
            'statuses' => Project::STATUSES,
            'taskStatuses' => \App\Models\Task::STATUSES,
            'taskPriorities' => \App\Models\Task::PRIORITIES,
        ]);
    }

    /**
     * Show the form for editing the specified project.
     */
    public function edit(Project $project): Response
    {
        return Inertia::render('Projects/Edit', [
            'project' => $project,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'statuses' => Project::STATUSES,
            'colors' => Project::COLORS,
        ]);
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'client_id' => 'nullable|exists:clients,id',
            'status' => 'required|in:' . implode(',', array_keys(Project::STATUSES)),
            'color' => 'nullable|string|max:7',
            'due_date' => 'nullable|date',
            'budget_hours' => 'nullable|numeric|min:0|max:9999',
        ]);

        // Verify client belongs to user if provided
        if (!empty($validated['client_id'])) {
            $client = Client::find($validated['client_id']);
            if (!$client || !$client->belongsToAuthUser()) {
                return back()->withErrors(['client_id' => 'Client invalide.']);
            }
        }

        $project->update($validated);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Projet mis à jour.');
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project): RedirectResponse
    {
        if (!$project->canBeDeleted()) {
            return back()->with('error', 'Ce projet ne peut pas être supprimé car il contient des entrées de temps.');
        }

        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projet supprimé.');
    }

    /**
     * Update project status quickly.
     */
    public function updateStatus(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(Project::STATUSES)),
        ]);

        $project->update(['status' => $validated['status']]);

        return back()->with('success', 'Statut mis à jour.');
    }

    /**
     * Reorder projects (for Kanban view).
     */
    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'projects' => 'required|array',
            'projects.*.id' => 'required|exists:projects,id',
            'projects.*.sort_order' => 'required|integer|min:0',
            'projects.*.status' => 'required|in:' . implode(',', array_keys(Project::STATUSES)),
        ]);

        foreach ($validated['projects'] as $item) {
            Project::where('id', $item['id'])
                ->update([
                    'sort_order' => $item['sort_order'],
                    'status' => $item['status'],
                ]);
        }

        return back();
    }

    /**
     * Archive or unarchive a project.
     */
    public function archive(Request $request, Project $project): RedirectResponse
    {
        $action = $request->get('action', 'archive');

        if ($action === 'archive') {
            $project->archive();
            $message = 'Projet archivé.';
        } else {
            $project->unarchive();
            $message = 'Projet restauré.';
        }

        return back()->with('success', $message);
    }
}
