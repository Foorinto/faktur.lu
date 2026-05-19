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
            'assignees' => 'nullable|array',
            'assignees.*' => ['string', 'regex:/^(employee|user):\d+$/'],
        ]);

        $assignees = $validated['assignees'] ?? [];
        unset($validated['assignees']);

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

        $task = $project->tasks()->create($validated);
        $this->syncAssignees($task, $assignees, $project);

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
            'assignees' => 'nullable|array',
            'assignees.*' => ['string', 'regex:/^(employee|user):\d+$/'],
        ]);

        $assignees = $request->has('assignees') ? ($validated['assignees'] ?? []) : null;
        unset($validated['assignees']);

        $task->update($validated);
        if ($assignees !== null) {
            $this->syncAssignees($task, $assignees, $task->project);
        }

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
     * Sync the many-to-many assignees on a task.
     * Each entry is "employee:N" or "user:N"; entries are validated against project membership.
     */
    private function syncAssignees(Task $task, array $assignees, ?Project $project): void
    {
        if (!$project) {
            return;
        }

        $employeeIds = [];
        $userIds = [];
        foreach ($assignees as $entry) {
            [$type, $id] = explode(':', $entry, 2);
            $id = (int) $id;
            if ($type === 'employee'
                && $project->activeEmployees()->where('employees.id', $id)->exists()) {
                $employeeIds[] = $id;
            }
            if ($type === 'user'
                && $project->collaborators()
                    ->where('users.id', $id)
                    ->wherePivotNotNull('accepted_at')
                    ->exists()) {
                $userIds[] = $id;
            }
        }

        // Sync employee + user pivots independently. Both share the task_assignees table
        // (an entry has either employee_id or user_id set, never both).
        \DB::table('task_assignees')->where('task_id', $task->id)->delete();
        $rows = [];
        $now = now();
        foreach (array_unique($employeeIds) as $eid) {
            $rows[] = ['task_id' => $task->id, 'employee_id' => $eid, 'user_id' => null, 'created_at' => $now, 'updated_at' => $now];
        }
        foreach (array_unique($userIds) as $uid) {
            $rows[] = ['task_id' => $task->id, 'employee_id' => null, 'user_id' => $uid, 'created_at' => $now, 'updated_at' => $now];
        }
        if ($rows) {
            \DB::table('task_assignees')->insert($rows);
        }
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
