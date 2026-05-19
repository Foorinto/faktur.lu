<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Store a newly created task in a project.
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'status' => 'nullable|in:' . implode(',', array_keys(Task::STATUSES)),
            'priority' => 'nullable|in:' . implode(',', array_keys(Task::PRIORITIES)),
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0|max:999',
            'parent_id' => 'nullable|exists:tasks,id',
            'assignee' => 'nullable|string|regex:/^(employee|user):\d+$/',
        ]);

        [$validated['assigned_to_employee_id'], $validated['assigned_to_user_id']]
            = $this->parseAssignee($validated['assignee'] ?? null, $project);
        unset($validated['assignee']);

        // Set default values
        $validated['status'] = $validated['status'] ?? Task::STATUS_BACKLOG;
        $validated['priority'] = $validated['priority'] ?? Task::PRIORITY_NORMAL;

        // Handle subtask creation
        if (!empty($validated['parent_id'])) {
            $parent = Task::findOrFail($validated['parent_id']);

            // Verify parent belongs to same project
            if ($parent->project_id !== $project->id) {
                abort(403);
            }

            $validated['depth'] = $parent->depth + 1;
            $maxOrder = $parent->children()->max('sort_order') ?? 0;
        } else {
            $validated['depth'] = 0;
            $maxOrder = $project->tasks()->rootTasks()->max('sort_order') ?? 0;
        }

        $validated['sort_order'] = $maxOrder + 1;

        $project->tasks()->create($validated);

        return back()->with('success', __('app.tasks_flash.created'));
    }

    /**
     * Store a subtask under an existing task.
     */
    public function storeSubtask(Request $request, Task $task): RedirectResponse
    {
        // Verify task belongs to user's project
        if (!$task->project || !$task->project->belongsToAuthUser()) {
            abort(403);
        }

        // Limit depth to 1 (only one level of subtasks)
        if ($task->depth >= 1) {
            return back()->with('error', __('app.tasks_flash.error_subtask_depth'));
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'priority' => 'nullable|in:' . implode(',', array_keys(Task::PRIORITIES)),
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0|max:999',
        ]);

        $task->addSubtask(array_merge($validated, [
            'status' => $task->status,
            'priority' => $validated['priority'] ?? Task::PRIORITY_NORMAL,
        ]));

        return back()->with('success', __('app.tasks_flash.subtask_created'));
    }

    /**
     * Update the specified task.
     */
    public function update(Request $request, Task $task): RedirectResponse
    {
        // Verify task belongs to user's project
        if (!$task->project || !$task->project->belongsToAuthUser()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'status' => 'nullable|in:' . implode(',', array_keys(Task::STATUSES)),
            'priority' => 'nullable|in:' . implode(',', array_keys(Task::PRIORITIES)),
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0|max:999',
            'assignee' => 'nullable|string|regex:/^(employee|user):\d+$/',
        ]);

        if (array_key_exists('assignee', $validated)) {
            [$validated['assigned_to_employee_id'], $validated['assigned_to_user_id']]
                = $this->parseAssignee($validated['assignee'], $task->project);
            unset($validated['assignee']);
        }

        $task->update($validated);

        // If status is done, mark as completed
        if (($validated['status'] ?? null) === Task::STATUS_DONE && !$task->is_completed) {
            $task->markAsComplete();
        }

        return back()->with('success', __('app.tasks_flash.updated'));
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Task $task): RedirectResponse
    {
        // Verify task belongs to user's project
        if (!$task->project || !$task->project->belongsToAuthUser()) {
            abort(403);
        }

        // Check if task or its subtasks have time entries
        $hasTimeEntries = $task->timeEntries()->exists()
            || $task->children()->whereHas('timeEntries')->exists();

        if ($hasTimeEntries) {
            return back()->with('error', __('app.tasks_flash.error_has_time_entries'));
        }

        // Delete all subtasks first
        $task->children()->delete();

        $task->delete();

        return back()->with('success', __('app.tasks_flash.deleted'));
    }

    /**
     * Toggle task completion status.
     */
    public function toggle(Task $task): RedirectResponse
    {
        // Verify task belongs to user's project
        if (!$task->project || !$task->project->belongsToAuthUser()) {
            abort(403);
        }

        $task->toggle();

        return back();
    }

    /**
     * Parse "employee:123" or "user:456" payload, validating membership on the project.
     * Returns [employeeId|null, userId|null].
     */
    private function parseAssignee(?string $assignee, ?Project $project): array
    {
        if (!$assignee || !$project) {
            return [null, null];
        }
        [$type, $id] = explode(':', $assignee, 2);
        $id = (int) $id;

        if ($type === 'employee') {
            $isActive = $project->activeEmployees()->where('employees.id', $id)->exists();
            return $isActive ? [$id, null] : [null, null];
        }
        if ($type === 'user') {
            $isMember = $project->collaborators()
                ->where('users.id', $id)
                ->wherePivotNotNull('accepted_at')
                ->exists();
            return $isMember ? [null, $id] : [null, null];
        }
        return [null, null];
    }

    /**
     * Reorder tasks within a project.
     */
    public function reorder(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:tasks,id',
            'tasks.*.sort_order' => 'required|integer|min:0',
            'tasks.*.status' => 'required|in:' . implode(',', array_keys(Task::STATUSES)),
            'tasks.*.parent_id' => 'nullable|exists:tasks,id',
        ]);

        foreach ($validated['tasks'] as $item) {
            $task = Task::find($item['id']);

            // Verify task belongs to this project
            if ($task && $task->project_id === $project->id) {
                $wasCompleted = $task->is_completed;
                $newStatus = $item['status'];
                $newParentId = $item['parent_id'] ?? null;

                // Calculate new depth
                $newDepth = 0;
                if ($newParentId) {
                    $parent = Task::find($newParentId);
                    if ($parent) {
                        $newDepth = $parent->depth + 1;
                    }
                }

                $task->update([
                    'sort_order' => $item['sort_order'],
                    'status' => $newStatus,
                    'parent_id' => $newParentId,
                    'depth' => $newDepth,
                ]);

                // Handle completion status based on new status
                if ($newStatus === Task::STATUS_DONE && !$wasCompleted) {
                    $task->update([
                        'is_completed' => true,
                        'completed_at' => now(),
                    ]);
                } elseif ($newStatus !== Task::STATUS_DONE && $wasCompleted) {
                    $task->update([
                        'is_completed' => false,
                        'completed_at' => null,
                    ]);
                }
            }
        }

        return back();
    }

    /**
     * Reorder tasks with support for subtasks (list view).
     */
    public function reorderList(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:tasks,id',
            'tasks.*.sort_order' => 'required|integer|min:0',
            'tasks.*.parent_id' => 'nullable|exists:tasks,id',
        ]);

        foreach ($validated['tasks'] as $item) {
            $task = Task::find($item['id']);

            // Verify task belongs to this project
            if ($task && $task->project_id === $project->id) {
                $newParentId = $item['parent_id'] ?? null;

                // Calculate new depth
                $newDepth = 0;
                if ($newParentId) {
                    $parent = Task::find($newParentId);
                    if ($parent) {
                        $newDepth = $parent->depth + 1;
                    }
                }

                $task->update([
                    'sort_order' => $item['sort_order'],
                    'parent_id' => $newParentId,
                    'depth' => $newDepth,
                ]);
            }
        }

        return back();
    }
}
