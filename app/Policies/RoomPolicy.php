<?php

namespace App\Policies;

use App\Models\HR\Room;
use App\Models\User;

class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Room $room): bool
    {
        return (int) $room->user_id === (int) $user->id;
    }

    /**
     * Only the organisation owner can manage rooms.
     */
    public function create(User $user): bool
    {
        return $user->isOrganizationOwner();
    }

    public function update(User $user, Room $room): bool
    {
        return (int) $room->user_id === (int) $user->id && $user->isOrganizationOwner();
    }

    public function delete(User $user, Room $room): bool
    {
        return $this->update($user, $room);
    }
}
