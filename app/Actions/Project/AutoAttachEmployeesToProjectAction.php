<?php

namespace App\Actions\Project;

use App\Models\HR\Employee;
use App\Models\Project;
use App\Models\ProjectEmployee;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AutoAttachEmployeesToProjectAction
{
    /**
     * Auto-attach all active employees of the organisation to a newly created project.
     * FEAT-081: called from ProjectController::store() after a project is created.
     */
    public function execute(Project $project): int
    {
        $employees = Employee::query()
            ->where('user_id', $project->user_id)
            ->where('status', 'active')
            ->get(['id', 'first_name', 'last_name', 'account_id', 'email_pro']);

        if ($employees->isEmpty()) {
            return 0;
        }

        $now = now();
        $rows = $employees->map(fn ($emp) => [
            'project_id' => $project->id,
            'employee_id' => $emp->id,
            'active' => true,
            'added_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        ProjectEmployee::insert($rows);

        // Dispatch notification emails (best-effort)
        foreach ($employees as $emp) {
            $email = $emp->account?->email ?? $emp->email_pro;
            if (! $email) continue;

            try {
                Mail::to($email)->queue(new \App\Mail\ProjectMemberAdded($project, $emp));
            } catch (\Throwable $e) {
                Log::warning('ProjectMemberAdded mail failed', [
                    'project_id' => $project->id,
                    'employee_id' => $emp->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $employees->count();
    }
}
