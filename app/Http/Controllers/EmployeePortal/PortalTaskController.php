<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EmployeePortal\Concerns\ResolvesEmployee;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PortalTaskController extends Controller
{
    use ResolvesEmployee;

    /**
     * Update task status (employee with active access on the project).
     */
    public function updateStatus(Request $request, int $taskId): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(Task::STATUSES)),
        ]);

        $task = $this->loadAuthorizedTask($taskId);

        $task->update([
            'status' => $validated['status'],
            'is_completed' => $validated['status'] === Task::STATUS_DONE,
            'completed_at' => $validated['status'] === Task::STATUS_DONE ? now() : null,
        ]);

        return back();
    }

    /**
     * Toggle task completion.
     */
    public function toggle(int $taskId): RedirectResponse
    {
        $task = $this->loadAuthorizedTask($taskId);
        $task->toggle();

        return back();
    }

    /**
     * Resolve the task and verify the current employee has active access on its project.
     */
    private function loadAuthorizedTask(int $taskId): Task
    {
        $employee = $this->employee();

        $task = Task::withoutGlobalScope('user')->findOrFail($taskId);

        $project = Project::withoutGlobalScope('user')
            ->where('id', $task->project_id)
            ->where('user_id', $employee->user_id)
            ->firstOrFail();

        $hasAccess = $employee->activeProjects()
            ->withoutGlobalScope('user')
            ->where('projects.id', $project->id)
            ->exists();

        abort_unless($hasAccess, 403);

        return $task;
    }
}
