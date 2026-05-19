<?php

namespace App\Jobs;

use App\Models\HR\Employee;
use App\Models\ProjectEmployee;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RemoveTerminatedEmployeeFromProjectsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $employeeId)
    {
    }

    public function handle(): void
    {
        $employee = Employee::withoutGlobalScope('user')->find($this->employeeId);

        if (! $employee || $employee->status !== 'terminated') {
            return;
        }

        $affected = ProjectEmployee::query()
            ->where('employee_id', $this->employeeId)
            ->where('active', true)
            ->with('project:id,title,user_id')
            ->get();

        if ($affected->isEmpty()) {
            return;
        }

        // Mark all as inactive (preserve history for time tracking)
        ProjectEmployee::query()
            ->where('employee_id', $this->employeeId)
            ->where('active', true)
            ->update(['active' => false]);

        // Send recap to org owner (admin RH)
        $orgUser = User::find($employee->user_id);
        if ($orgUser && $orgUser->email) {
            try {
                Mail::to($orgUser->email)->queue(
                    new \App\Mail\TerminatedEmployeeProjectsCleanupSummary(
                        $employee->fresh(),
                        $affected->pluck('project')->filter()->values()->all()
                    )
                );
            } catch (\Throwable $e) {
                Log::warning('TerminatedEmployeeProjectsCleanupSummary mail failed', [
                    'employee_id' => $this->employeeId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
