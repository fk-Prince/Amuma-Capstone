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
        $user->load('employee.permissions.modules');
        $employee = $user->employee;
        $permissions = $user->employee?->permissions ?? collect();

        $branchIds = $permissions->pluck('branch_id')->filter()->unique()->values();

        $branchModels = $this->branchRepository->getUserBranches($branchIds->all());

        $branches = $permissions
            ->groupBy('branch_id')
            ->map(function ($perms, $branchId) use ($branchModels, $employee) {
                $branch = $branchModels->get($branchId);

                if (!$branch || !$branch->uuid) {
                    return null;
                }

                $employeeBranch = $employee->employeeBranch
                    ->firstWhere('branch_id', $branchId);

                // $employeeBranches = $employee->employeeBranch;

                $location = $branch?->location;

                return [
                    'uuid' => $branch?->uuid,
                    'name' => $branch?->name,
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
                        'agency_name' => $branch?->agencies->name,
                        'agency_description' => $branch?->agencies->description,
                        'location' => $branch?->agencies->locations,
                    ],
                    'settings' => $branch->settings,
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
