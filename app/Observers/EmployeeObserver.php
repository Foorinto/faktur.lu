<?php

namespace App\Observers;

use App\Jobs\RemoveTerminatedEmployeeFromProjectsJob;
use App\Models\HR\Employee;

class EmployeeObserver
{
    /**
     * Handle the Employee "updated" event.
     *
     * FEAT-081: when an employee is marked as terminated, remove them
     * (active=false) from all their active projects via a queued job.
     * The portal access is left intact - it's the HR admin's responsibility
     * to manually disable the portal via the dedicated button.
     */
    public function updated(Employee $employee): void
    {
        if (! $employee->wasChanged('status')) {
            return;
        }

        if ($employee->status !== 'terminated') {
            return;
        }

        // Previous status was something else (active / long_leave) → trigger cleanup
        $previousStatus = $employee->getOriginal('status');
        if ($previousStatus === 'terminated') {
            return;
        }

        RemoveTerminatedEmployeeFromProjectsJob::dispatch($employee->id);
    }
}
