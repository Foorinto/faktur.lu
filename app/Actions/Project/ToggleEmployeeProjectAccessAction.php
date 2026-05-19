<?php

namespace App\Actions\Project;

use App\Models\HR\Employee;
use App\Models\Project;
use App\Models\ProjectEmployee;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ToggleEmployeeProjectAccessAction
{
    /**
     * Toggle the active flag on a project_employees pivot row.
     * Creates the row with active=true if it doesn't exist yet.
     * Sends a notification email reflecting the new state.
     */
    public function execute(Project $project, Employee $employee): ProjectEmployee
    {
        $pivot = ProjectEmployee::firstOrNew([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
        ]);

        if (! $pivot->exists) {
            $pivot->active = true;
            $pivot->added_at = now();
        } else {
            $pivot->active = ! $pivot->active;
        }

        $pivot->save();

        $email = $employee->account?->email ?? $employee->email_pro;
        if ($email) {
            try {
                if ($pivot->active) {
                    Mail::to($email)->queue(new \App\Mail\ProjectMemberAdded($project, $employee));
                } else {
                    Mail::to($email)->queue(new \App\Mail\ProjectMemberRemoved($project, $employee));
                }
            } catch (\Throwable $e) {
                Log::warning('ProjectMember toggle mail failed', [
                    'project_id' => $project->id,
                    'employee_id' => $employee->id,
                    'active' => $pivot->active,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $pivot;
    }
}
