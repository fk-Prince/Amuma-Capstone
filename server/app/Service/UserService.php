<?php

namespace App\Service;

use App\Models\Branch;
use App\Models\User;
use App\Repository\BranchRepository;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserService
{
    private UserRepository $userRepository;
    private BranchRepository $branchRepository;
    public function __construct(UserRepository $userRepository, BranchRepository $branchRepository)
    {
        $this->userRepository = $userRepository;
        $this->branchRepository = $branchRepository;
    }

    public function getUserBranch(User $user)
    {
        $user->load('roles');

        $branchIds = $user->roles->pluck('pivot.branch_id')->filter()->unique()->values();

        $branchModels = $this->branchRepository->getUserBranches($branchIds->all());

        $branches = $user->roles
            ->groupBy(fn($role) => $role->pivot->branch_id)
            ->map(function ($roles, $branchId) use ($branchModels) {
                $branch = $branchModels->get($branchId);
                $location = $branch?->locations;
                // $subscription = $branch?->subscriptions->first();
                // $subscription = $branch?->subscriptions;


                return [
                    'uuid' => $branch?->uuid,
                    'name' => $branch?->name,

                    'location' => $location ? [
                        'street' => $location->street,
                        'city' => $location->city,
                        'province' => $location->province,
                        'country' => $location->country,
                        'address' => $location->full_address,
                    ] : null,

                    // 'plan' => $subscription && $subscription->plans ? [
                    //     'plan_code' => $subscription->plans->plan_code,
                    //     'name' => $subscription->plans->name,
                    // ] : null,

                    'plan' => $branch?->subscriptions
                        ? $branch->subscriptions->map(function ($subscription) {
                            return [
                                'plan_code' => $subscription->plans->plan_code,
                                'name' => $subscription->plans->name,
                            ];
                        })->values()
                        : [],

                    'roles' => $roles->map(function ($role) {
                        return [
                            'role_type' => $role->role_type,
                            'is_active' => $role->pivot->is_active,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'branches' => $branches,
            ],
        ], 200);
    }

    public function fetchMe(User $user)
    {
        $user = $user->load([
            'userRoles.role',
            'userRoles.branch'
        ]);
        return [
            'user' => [
                'uuid' => $user->uuid,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'provider' => $user->provider,
                'phone_number' => $user->phone_number,
                'is_active' => $user->is_active,
                'is_verified' => $user->is_verified,
                'avatar' => $user->avatar,
                'address' => $user->location?->full_address,
                'roles' => $user->userRoles->map(function ($ur) {
                    return [
                        'is_active' => $ur->is_active,
                        'role' => $ur->role?->role_type,
                        'branch' => $ur->branch?->uuid,
                    ];
                })
            ],
        ];
    }
}
