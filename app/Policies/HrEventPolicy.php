<?php

namespace App\Policies;

use App\Models\HR\HrEvent;
use App\Models\User;

class HrEventPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, HrEvent $event): bool
    {
        return $event->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->id !== null;
    }

    public function update(User $user, HrEvent $event): bool
    {
        if ($event->user_id !== $user->id) {
            return false;
        }

        // Owner of organisation OR creator (employee linked to user account)
        if ($user->isOrganizationOwner()) {
            return true;
        }

        $employee = $user->linkedEmployee;
        return $employee && $event->created_by_employee_id === $employee->id;
    }

    public function delete(User $user, HrEvent $event): bool
    {
        return $this->update($user, $event);
    }
}
