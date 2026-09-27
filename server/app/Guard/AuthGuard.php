<?php

namespace App\Guard;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Enums\RoleEnum;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\User;
use Exception;
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

    public static function requireModule(?User $user,  string|bool $branchId = false, ModuleEnum|array $module, PermissionAction $action)
    {
        $user = self::requireUser($user);

        if (!$user->relationLoaded('employee')) {
            $user->load('employee.permissions.modules');
        }

        $employee = $user->employee;

        if (!$employee) {
            throw new Exception('Insufficient permissionsa', 403);
        }

        // The person who registered an agency has full access to every
        // branch under it, regardless of whether they have a permission row
        // on that specific branch — an agency_owner row on any one of the
        // agency's branches is enough.
        if ($branchId !== false && self::ownsAgencyFor($employee, $branchId)) {
            return $user;
        }

        $moduleNames = collect($module)
            ->map(fn(ModuleEnum $module) => $module->value)
            ->values();

        $hasPermission = $employee->permissions->contains(function ($permission) use ($moduleNames, $action, $branchId) {
            // return $permission->modules?->module_name === $module->value
            //     && ($permission->{$action->value} ?? false)
            //     && ($branchId === false || $permission->branch_id == $branchId);
            return $moduleNames->contains($permission->modules?->module_name)
                && ($permission->{$action->value} ?? false)
                && ($branchId === false || $permission->branch_id == $branchId);
        });

        if (!$hasPermission) {
            throw new Exception('Insufficient permissions', 403);
        }

        return $user;
    }

    private static function ownsAgencyFor(Employee $employee, string $branchId): bool
    {
        $agencyId = Branch::where('branch_id', $branchId)->value('agency_id');

        if (!$agencyId) {
            return false;
        }

        return EmployeeBranch::where('employee_id', $employee->employee_id)
            ->where('role_name', RoleEnum::AgencyOwner->value)
            ->whereHas('branches', fn($q) => $q->where('agency_id', $agencyId))
            ->exists();
    }
}
