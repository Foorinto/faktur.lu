<?php

namespace App\Policies;

use App\Models\Import\ImportSession;
use App\Models\User;

class ImportSessionPolicy
{
    public function view(User $user, ImportSession $session): bool
    {
        return (int) $session->user_id === (int) $user->id;
    }

    public function update(User $user, ImportSession $session): bool
    {
        return (int) $session->user_id === (int) $user->id;
    }

    public function delete(User $user, ImportSession $session): bool
    {
        return (int) $session->user_id === (int) $user->id;
    }
}
