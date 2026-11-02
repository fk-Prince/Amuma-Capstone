<?php

namespace App\Broadcasting;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketChannel
{
    public function join(User $user, string $uuid): bool
    {
        $ticket = SupportTicket::query()
            ->where('uuid', $uuid)
            ->first(['support_ticket_id', 'branch_id', 'user_id']);

        if (!$ticket) {
            return false;
        }

        return $user->isSystemOwner || (int) $ticket->user_id === (int) $user->user_id;
    }
}
