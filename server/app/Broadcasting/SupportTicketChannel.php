<?php

namespace App\Broadcasting;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
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

        if ($user->isSystemOwner || (int) $ticket->user_id === (int) $user->user_id) {
            return true;
        }

        if ($ticket->branch_id === null) {
            return false;
        }

        try {
            AuthGuard::requireModule($user, $ticket->branch_id, ModuleEnum::ManageSubscription, PermissionAction::Read);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
