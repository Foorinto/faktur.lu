<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectMemberPolicy
{
    /**
     * Only the organisation owner (or admin) can manage project members.
     */
    public function manage(User $user, Project $project): bool
    {
        return $project->user_id === $user->id && $user->isOrganizationOwner();
    }

    public function view(User $user, Project $project): bool
    {
        return $project->user_id === $user->id;
    }
}
