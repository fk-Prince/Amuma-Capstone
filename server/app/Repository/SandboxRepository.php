<?php

namespace App\Repository;

use App\Enums\RoleEnum;
use App\Models\Agency;
use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\EmployeePermission;
use App\Models\Location;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;

class SandboxRepository
{
    public function branchExists(string $uuid): bool
    {
        return Branch::where('uuid', $uuid)->exists();
    }

    public function setAgencyLock(string $agencyUuid, string $state): void
    {
        Subscription::whereHas('agency', fn($q) => $q->where('uuid', $agencyUuid))
            ->update(['status' => self::statusForState($state)]);
    }

    private static function statusForState(?string $state): string
    {
        return match ($state) {
            'ended' => Subscription::STATUS_EXPIRED,
            'review' => Subscription::STATUS_PENDING,
            default => Subscription::STATUS_ACTIVE,
        };
    }

    public function hasBaseData(): bool
    {
        return Module::exists() && Plan::exists();
    }

    public function createBranchWithOwner(array $payload): Branch
    {
        $plan = Plan::where('plan_code', $payload['plan']['plan_code'])
            ->where('type', $payload['plan']['type'])
            ->first();

        if (!$plan) {
            throw new Exception('Plan not found in the sandbox.', 422);
        }

        $owner = $this->owner($payload['owner']);
        $agency = $this->agency($payload['agency'], $owner);

        $branch = new Branch();
        $branch->forceFill([
            'uuid' => $payload['branch']['uuid'],
            'agency_id' => $agency->agency_id,
            'location_id' => $this->location($payload['branch']['location'] ?? null)?->location_id,
            'name' => $payload['branch']['name'],
            'description' => $payload['branch']['description'] ?? null,
            'email' => $payload['branch']['email'] ?? null,
            'contact_number' => $payload['branch']['contact_number'] ?? null,
            'image' => $payload['branch']['image'] ?? null,
            'settings' => $payload['branch']['settings'] ?? null,
            'status' => Branch::STATUS_VERIFIED,
        ])->save();

        $subscription = Subscription::where('agency_id', $agency->agency_id)->first()
            ?? Subscription::create([
                ...Subscription::liveTerms($plan->plan_id),
                'agency_id' => $agency->agency_id,
                'status' => self::statusForState($payload['state'] ?? null),
            ]);

        BranchSubscription::create([
            'subscription_id' => $subscription->subscription_id,
            'branch_id' => $branch->branch_id,
            'status' => BranchSubscription::STATUS_APPROVED,
            'type' => BranchSubscription::TYPE_INCLUDED,
        ]);

        $employee = $owner->employee;

        EmployeeBranch::updateOrCreate(
            ['employee_id' => $employee->employee_id, 'branch_id' => $branch->branch_id],
            ['role_name' => RoleEnum::AgencyOwner->value, 'status' => EmployeeBranch::STATUS_ACTIVE]
        );

        $permissions = RoleEnum::AgencyOwner->permissions();

        foreach (Module::all() as $module) {
            EmployeePermission::updateOrCreate(
                [
                    'employee_id' => $employee->employee_id,
                    'branch_id' => $branch->branch_id,
                    'module_id' => $module->module_id,
                ],
                EmployeePermission::grantColumns($permissions[$module->module_name] ?? [])
            );
        }

        return $branch;
    }

    public function createFamily(string $email): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            ['password' => Hash::make('password'), 'provider' => 'local']
        );

        $user->client()->updateOrCreate(
            ['user_id' => $user->user_id],
            [
                'first_name' => 'Demo',
                'last_name' => 'Family',
                'avatar' => 'https://ui-avatars.com/api/?name=DF',
            ]
        );
        return $user;
    }

    private function owner(array $data): User
    {
        $user = User::firstOrNew(['email' => $data['email']]);
        $user->provider = $data['provider'] ?? 'local';

        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        Employee::updateOrCreate(
            ['user_id' => $user->user_id],
            [
                'first_name' => $data['first_name'] ?? 'Agency',
                'last_name' => $data['last_name'] ?? 'Owner',
                'avatar' => $data['avatar'] ?? null,
            ]
        );

        return $user->load('employee');
    }

    private function agency(array $data, User $owner): Agency
    {
        $agency = Agency::where('uuid', $data['uuid'])->first();

        if ($agency) {
            return $agency;
        }

        $agency = new Agency();
        $agency->forceFill([
            'uuid' => $data['uuid'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'email' => $data['email'] ?? null,
            'image' => $data['image'] ?? null,
            'location_id' => $this->location($data['location'] ?? null)?->location_id,
            'registered_by' => $owner->user_id,
            'status' => Agency::STATUS_VERIFIED,
        ])->save();

        return $agency;
    }

    private function location(?array $data): ?Location
    {
        return $data ? Location::create($data) : null;
    }
}
