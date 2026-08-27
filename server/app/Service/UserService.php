<?php

namespace App\Service;


use App\Models\Location;
use App\Models\User;
use App\Repository\BranchRepository;
use App\Repository\LocationRepository;
use App\Repository\UserRepository;
use App\Service\External\SupabaseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class UserService
{
    public function __construct(private BranchRepository $branchRepository) {}

    public function getUserBranch(User $user)
    {
        $user->load('employee.permissions.modules', 'employee.employeeBranch');
        $employee = $user->employee;
        $permissionsByBranch = ($employee?->permissions ?? collect())->groupBy('branch_id');

        $employeeBranches = $employee?->employeeBranch ?? collect();
        $branchIds = $employeeBranches->pluck('branch_id')->filter()->unique()->values();

        $branchModels = $this->branchRepository->getUserBranches($branchIds->all());

        $branches = $employeeBranches
            ->map(function ($employeeBranch) use ($branchModels, $permissionsByBranch) {
                $branch = $branchModels->get($employeeBranch->branch_id);

                if (!$branch || !$branch->uuid) {
                    return null;
                }

                $perms = $permissionsByBranch->get($employeeBranch->branch_id, collect());

                $location = $branch?->location;


                $settings = $branch->settings ?? [];

                // Older rows stored these as strings ("1", "8"), which leaves
                // checkboxes unchecked on the client.
                foreach (
                    ['enable_booking_pre_admission', 'enable_booking_complete_admission', 'is_open']
                    as $key
                ) {
                    if (array_key_exists($key, $settings)) {
                        $settings[$key] = filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN);
                    }
                }

                foreach (['reserved_walkin_slots', 'minimum_adl_hours'] as $key) {
                    if (array_key_exists($key, $settings)) {
                        $settings[$key] = (int) $settings[$key];
                    }
                }

                $settings['termination_fee_percent'] = max(0, min(100, (float) ($settings['termination_fee_percent'] ?? 0)));
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


    public function profile(User $user)
    {
        $user->load([
            'employee.locations',
            'client.location',
            'systemOwner.location',
        ]);

        $profile = $user->employee ?? $user->client ?? $user->systemOwner;

        $location = $user->employee?->locations
            ?? $user->client?->location
            ?? $user->systemOwner?->location;

        return response()->json([
            'data' => [
                'uuid'         => $user->uuid,
                'email'        => $user->email,
                'provider'     => $user->provider,
                'created_at'   => $user->created_at,
                // Google-only accounts have no local password to change.
                'has_password' => !empty($user->getAuthPassword()),

                'first_name'   => $profile?->first_name,
                'last_name'    => $profile?->last_name,
                'avatar'       => $profile?->avatar,
                // platform_admins has no phone column.
                'phone_number' => $user->employee?->phone_number
                    ?? $user->client?->phone_number,
                'birth_date'   => $user->employee?->birth_date,

                'location' => $location ? [
                    'street'       => $location->street,
                    'city'         => $location->city,
                    'province'     => $location->province,
                    'country'      => $location->country,
                    'latitude'     => $location->latitude,
                    'longitude'    => $location->longitude,
                    'full_address' => $location->full_address,
                ] : null,

                'roles' => [
                    'is_employee'     => $user->isEmployee,
                    'is_client'       => $user->isClient,
                    'is_system_owner' => $user->isSystemOwner,
                ],
            ],
        ]);
    }

    public function updateProfile(User $user, array $payload)
    {
        return DB::transaction(function () use ($user, $payload) {
            $user->load([
                'employee.locations',
                'client.location',
                'systemOwner.location',
            ]);

            $avatarUrl = null;

            if (!empty($payload['avatar']) && $payload['avatar'] instanceof UploadedFile) {
                $stored = SupabaseService::store($payload['avatar']);
                $avatarUrl = $stored['url'] ?? null;
            }

            $userChanges = ['email' => $payload['email']];

            if (!empty($payload['password'])) {
                $userChanges['password'] = $this->resolveNewPassword($user, $payload);
            }

            $user->update($userChanges);

            $locationId = $this->syncLocation($user, $payload);

            $shared = array_filter([
                'first_name' => $payload['first_name'] ?? null,
                'last_name'  => $payload['last_name'] ?? null,
                'avatar'     => $avatarUrl,
            ], fn($value) => $value !== null);

            $withLocation = $locationId
                ? $shared + ['location_id' => $locationId]
                : $shared;

            $user->employee?->update($withLocation + array_filter([
                'phone_number' => $payload['phone_number'] ?? null,
                'birth_date'   => $payload['birth_date'] ?? null,
            ], fn($value) => $value !== null));

            $user->client?->update($withLocation + array_filter([
                'phone_number' => $payload['phone_number'] ?? null,
            ], fn($value) => $value !== null));

            $user->systemOwner?->update($withLocation);

            return $this->profile($user->fresh());
        });
    }


    private function resolveNewPassword(User $user, array $payload): string
    {
        $existing = $user->getAuthPassword();

        if (!empty($existing)) {
            $current = $payload['current_password'] ?? '';

            if (!Hash::check($current, $existing)) {
                throw ValidationException::withMessages([
                    'current_password' => __('Your current password is incorrect.'),
                ]);
            }
        }

        return $payload['password'];
    }


    private function syncLocation(User $user, array $payload): ?int
    {
        $fields = array_filter([
            'street'    => $payload['street'] ?? null,
            'city'      => $payload['city'] ?? null,
            'province'  => $payload['province'] ?? null,
            'country'   => $payload['country'] ?? null,
            'latitude'  => $payload['latitude'] ?? null,
            'longitude' => $payload['longitude'] ?? null,
        ], fn($value) => $value !== null && $value !== '');

        if (empty($fields)) {
            return null;
        }

        $fields['full_address'] = null;

        $location = $user->employee?->locations
            ?? $user->client?->location
            ?? $user->systemOwner?->location;

        if ($location) {
            $location->update($fields);

            return $location->location_id;
        }

        return Location::create($fields)->location_id;
    }
}
