<?php

namespace App\Enums;

enum PermissionAction: string
{
    case Read = 'can_read';
    case Create = 'can_create';
    case Update = 'can_update';
    case Export = 'can_export';
    case Approve = 'can_approve';
    case Reject = 'can_reject';
    case Assign = 'can_assign';
    case Admit = 'can_admit';
    case Discharge = 'can_discharge';
    case ForceDischarge = 'can_force_discharge';
    case ApproveWithdrawal = 'can_approve_withdrawal';
    case Renew = 'can_renew';

    public static function columns(): array
    {
        return array_map(fn(self $action) => $action->value, self::cases());
    }
}
