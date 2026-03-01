<?php

namespace App\Http\Controllers\EmployeePortal\Concerns;

use App\Models\HR\Employee;

trait ResolvesEmployee
{
    protected function employee(): Employee
    {
        return Employee::withoutGlobalScope('user')
            ->where('account_id', auth()->id())
            ->firstOrFail();
    }
}
