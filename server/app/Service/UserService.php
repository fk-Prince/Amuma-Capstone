<?php

namespace App\Service;


use App\Models\User;
use App\Repository\BranchRepository;
use App\Repository\LocationRepository;
use App\Repository\UserRepository;


class UserService
{
    private BranchRepository $branchRepository;
    public function __construct(UserRepository $userRepository, BranchRepository $branchRepository, LocationRepository $locationRepository)
    {
        $this->branchRepository = $branchRepository;
    }

    public function getUserBranch(User $user)
    {
        $user->load('employee.permissions.modules', 'employee.employeeBranch');
        $employee = $user->employee;
        $permissionsByBranch = ($employee?->permissions ?? collect())->groupBy('branch_id');

        $employeeBranches = $employee?->employeeBranch ?? collect();
        $branchIds = $employeeBranches->pluck('branch_id')->filter()->unique()->values();

        $branchModels = $this->branchRepository->getUserBranches($branchIds->all());

        // Branch membership is driven by EmployeeBranch (the actual branch
        // assignment), not by having at least one EmployeePermission row —
        // an employee can be assigned to a branch before any module
        // permissions are configured for them, and they should still see
        // that branch (just with an empty permissions list) instead of it
        // silently disappearing from this list.
        $branches = $employeeBranches
            ->map(function ($employeeBranch) use ($branchModels, $permissionsByBranch) {
                $branch = $branchModels->get($employeeBranch->branch_id);

                if (!$branch || !$branch->uuid) {
                    return null;
                }

                $perms = $permissionsByBranch->get($employeeBranch->branch_id, collect());

                $location = $branch?->location;


                $settings = $branch->settings ?? [];
                $settings['termination_fee'] = $settings['termination_fee'] ?? 0.20;
                return [
                    'uuid' => $branch?->uuid,
                    'name' => $branch?->name,
                    'email' => $branch?->email,
                    'is_verified' => $branch?->is_verified,
                    'description' => $branch?->description,
                    'contact_number' => $branch?->contact_number,
                    'role_name' => $employeeBranch?->role_name,
                    'assignment_type' => $employeeBranch?->assignment_type,
                    'image' => $branch?->image,
                    'location' => $location ? [
                        'street' => $location->street,
                        'city' => $location->city,
                        'province' => $location->province,
                        'country' => $location->country,
                        'longitude' => $location->longitude,
                        'latitude' => $location->latitude,
                        'address' => $location->full_address,
                    ] : null,
                    'agency' => [
                        'agency_id'  => $branch?->agencies->agency_id,
                        'name' => $branch?->agencies->name,
                        'email' => $branch?->agencies->email,
                        'description' => $branch?->agencies->description,
                        'location' => $branch?->agencies->locations,
                        'image' => $branch->agencies->image,
                        'is_verified' => $branch->agencies->is_verified,
                    ],

                    'settings' => $settings,
                    'plan' => $branch?->subscriptions
                        ? $branch->subscriptions->map(function ($subscription) {
                            return [
                                'plan_code' => $subscription->plans->plan_code,
                                'name' => $subscription->plans->name,
                            ];
                        })->values()
                        : [],

                    'permissions' => $perms->map(function ($permission) {
                        return [
                            'module_name'     => $permission->modules?->module_name,
                            'can_read'   => $permission->can_read,
                            'can_update' => $permission->can_update,
                            'can_create' => $permission->can_create,
                            'can_approve' => $permission->can_approve
                        ];
                    })->values(),
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'data' => [
                'branches' => $branches,
            ],
        ], 200);
    }

    public function fetchMe(User $user)
    {
        $user->load(['employee', 'client', 'systemOwner']);

        return [
            'user' => $user,
            'has_booking' => $user->bookings()->exists(),
        ];
    }
}
