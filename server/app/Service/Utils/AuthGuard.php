<?php

namespace App\Service\Utils;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;

class AuthGuard
{

    public static function requireUser(User $user)
    {
        if (!$user) {
            throw new UnauthorizedException('User not authenticated', 403);
        }

        return $user;
    }

    public static function requireModule(?User $user,  string|bool $branchId = false, ModuleEnum $module, PermissionAction $action)
    {
        $user = self::requireUser($user);

        if (!$user->relationLoaded('employee')) {
            $user->load('employee.permissions.modules');
        }

        $employee = $user->employee;

        if (!$employee) {
            throw new Exception('Insufficient permissionsa', 403);
        }

        // $hasPermission = $employee->permissions->contains(function ($permission) use ($module, $action) {
        //     return $permission->modules?->module_name === $module->value
        //         && (bool) $permission->{$action->value};
        // });
        $hasPermission = $employee->permissions->contains(function ($permission) use ($module, $action, $branchId) {
            return $permission->modules?->module_name === $module->value
                && ($permission->{$action->value} ?? false)
                && ($branchId === false || $permission->branch_id == $branchId);
        });

        if (!$hasPermission) {
            throw new Exception('Insufficient permissionsb', 403);
        }

        return $user;
    }
}
