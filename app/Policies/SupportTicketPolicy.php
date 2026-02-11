<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    /**
     * Determine whether the user can view the ticket.
     */
    public function view(User $user, SupportTicket $ticket): bool
    {
        return $user->id === $ticket->user_id;
    }

    /**
     * Determine whether the user can reply to the ticket.
     */
    public function reply(User $user, SupportTicket $ticket): bool
    {
        return $user->id === $ticket->user_id && $ticket->isOpen();
    }
}
