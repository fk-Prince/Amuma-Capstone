<?php

namespace App\Guard;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Enums\RoleEnum;
use App\Models\Branch;
use App\Models\BranchSubscription;
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
        $user = self::requirePermission($user, $branchId, $module, $action);

        self::requireLiveSubscription(
            $branchId,
            collect($module)->map(fn(ModuleEnum $module) => $module->value)->values(),
            $action
        );

        return $user;
    }

    public static function requirePermission(?User $user, string|bool $branchId, ModuleEnum|array $module, PermissionAction $action): User
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

        self::requireOpenSubscription(
            $branchId,
            $moduleNames->every(fn($name) => $name === ModuleEnum::BillingAndInvoices->value)
        );
    }

    public static function requireOpenSubscription(string|int $branchId, bool $billing = false): void
    {
        $link = BranchSubscription::where('branch_id', $branchId)
            ->latest('branch_subscription_id')
            ->first();

        if ($link?->isDeactivated() && !$billing) {
            throw new Exception(
                "This branch is deactivated, so it's view-only. Reactivate it in Manage Subscription to make changes.",
                402
            );
        }

        $subscription = Subscription::query()
            ->whereHas('branchLinks', fn($q) => $q->where('branch_id', $branchId))
            ->latest('created_at')
            ->first();

        if (!$subscription) {
            return;
        }

        if ($subscription->isLockedForPayment()) {
            throw new Exception(
                'Your free trial has ended, so this branch is read-only. Subscribe from Manage Subscription to unlock it.',
                402
            );
        }

        $expired = $subscription->status === Subscription::STATUS_EXPIRED
            || (
                $subscription->status === Subscription::STATUS_ACTIVE
                && $subscription->end_date?->copy()->startOfDay()->lt(now()->startOfDay())
                && !$subscription->pendingPlanIsDue()
            );

        $ended = $subscription->status === Subscription::STATUS_CANCELLED
            || ($expired && !$billing);

        if ($ended) {
            throw new Exception(
                "This branch's subscription has ended. Records can still be viewed, but changes are blocked until it is renewed.",
                402
            );
        }
    }
}
