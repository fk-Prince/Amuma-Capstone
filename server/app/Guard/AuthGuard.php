<?php

namespace App\Guard;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Enums\RoleEnum;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\Subscription;
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

        $user->loadMissing('employee.permissions.modules');

        $employee = $user->employee;

        if (!$employee) {
            throw new Exception('Insufficient permissions', 403);
        }


        // if ($branchId !== false && self::ownsAgencyFor($employee, $branchId)) {
        //     return $user;
        // }

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

        self::requireLiveSubscription($branchId, $moduleNames, $action);

        return $user;
    }


    private static function requireLiveSubscription(string|bool $branchId, mixed $moduleNames, PermissionAction $action): void
    {
        if ($branchId === false) {
            return;
        }

        if (in_array($action, [PermissionAction::Read, PermissionAction::Export], true)) {
            return;
        }

        if ($moduleNames->contains(ModuleEnum::ManageSubscription->value)) {
            return;
        }

        $subscription = Subscription::query()
            ->whereHas('branchLinks', fn($q) => $q->where('branch_id', $branchId))
            ->latest('created_at')
            ->first();

        if (!$subscription) {
            return;
        }

        $ended = in_array($subscription->status, [Subscription::STATUS_EXPIRED, Subscription::STATUS_CANCELLED], true)
            || (
                $subscription->status === Subscription::STATUS_ACTIVE
                && $subscription->end_date?->copy()->startOfDay()->lt(now()->startOfDay())
                && !$subscription->pendingPlanIsDue()
            );

        if ($ended) {
            throw new Exception(
                "This branch's subscription has ended. Records can still be viewed, but changes are blocked until it is renewed.",
                402
            );
        }
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
