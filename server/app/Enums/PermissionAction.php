<?php

namespace App\Enums;

enum PermissionAction: string
{
    case Read = 'can_read';
    case Create = 'can_create';
    case Update = 'can_update';
    case Export = 'can_export';
    case Assign = 'can_assign';
    case ForceDischarge = 'can_force_discharge';
    case Renew = 'can_renew';

    public static function columns(): array
    {
        return array_map(fn(self $action) => $action->value, self::cases());
    }
}
