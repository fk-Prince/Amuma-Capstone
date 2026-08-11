<?php

namespace App\Enums;

enum PermissionAction: string
{
    case Read = 'can_read';
    case Create = 'can_create';
    case Update = 'can_update';
    case Approve = 'can_approve';
    case Assign = 'can_assign';
}
