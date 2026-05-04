<?php

namespace App\Http\Controllers\Collaborator;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;

class CollaboratorTaskController extends Controller
{
    private function getOrganizationOwnerId(Request $request): int
    {
        $organization = $request->user()->getOrganization();
        abort_unless($organization, 403);

        return $organization->user_id;
    }

    private function findVisibleProject(int $projectId, int $ownerId): Project
    {
        return Project::withoutGlobalScopes()
            ->where('id', $projectId)
            ->where('user_id', $ownerId)
            ->visibleToCollaborators()
            ->firstOrFail();
    }

    public function store(Request $request, int $project)
    {
        $ownerId = $this->getOrganizationOwnerId($request);
        $project = $this->findVisibleProject($project, $ownerId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|string|in:low,normal,high',
            'due_date' => 'nullable|date',
            'parent_id' => 'nullable|integer|exists:tasks,id',
        ]);

        $depth = 0;
        if (!empty($validated['parent_id'])) {
            $parent = Task::findOrFail($validated['parent_id']);
            if ($parent->project_id !== $project->id) {
                abort(403);
            }
            $depth = $parent->depth + 1;
        }

        $maxSort = $project->tasks()
            ->where('parent_id', $validated['parent_id'] ?? null)
            ->max('sort_order') ?? 0;

        Task::create([
            'project_id' => $project->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'depth' => $depth,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'] ?? 'normal',
            'due_date' => $validated['due_date'] ?? null,
            'sort_order' => $maxSort + 1,
        ]);

        return back()->with('success', __('app.tasks_flash.created'));
    }

    public function update(Request $request, Task $task)
    {
        $ownerId = $this->getOrganizationOwnerId($request);

        // Verify task belongs to a visible project
        $project = Project::withoutGlobalScopes()
            ->where('id', $task->project_id)
            ->where('user_id', $ownerId)
            ->visibleToCollaborators()
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:' . implode(',', array_keys(Task::STATUS_LABELS)),
            'priority' => 'nullable|string|in:low,normal,high',
            'due_date' => 'nullable|date',
            'is_completed' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        if (isset($validated['is_completed'])) {
            $validated['is_completed']
                ? $task->markAsComplete()
                : $task->markAsIncomplete();
            unset($validated['is_completed']);
        }

        $task->update($validated);

        return back()->with('success', __('app.tasks_flash.updated'));
    }

    public function destroy(Request $request, Task $task)
    {
        $ownerId = $this->getOrganizationOwnerId($request);

        Project::withoutGlobalScopes()
            ->where('id', $task->project_id)
            ->where('user_id', $ownerId)
            ->visibleToCollaborators()
            ->firstOrFail();

        $task->delete();

        return back()->with('success', __('app.tasks_flash.deleted'));
    }

    public function toggle(Request $request, Task $task)
    {
        $ownerId = $this->getOrganizationOwnerId($request);

        Project::withoutGlobalScopes()
            ->where('id', $task->project_id)
            ->where('user_id', $ownerId)
            ->visibleToCollaborators()
            ->firstOrFail();

        $task->toggle();

        return back();
    }
}
