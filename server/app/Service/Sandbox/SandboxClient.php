<?php

namespace App\Service\Sandbox;

use App\Models\Branch;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SandboxClient
{
    public ?string $lastError = null;

    public function enabled(): bool
    {
        return !config('app.sandbox')
            && (bool) config('services.sandbox.url')
            && (bool) config('services.sandbox.secret');
    }

    public function provisionBranch(Branch $branch): void
    {
        if (!$this->enabled()) {
            return;
        }

        $branchId = $branch->branch_id;

        DB::afterCommit(fn() => $this->send($branchId));
    }

    public function send(int $branchId): bool
    {
        $this->lastError = null;

        try {
            $payload = $this->payload($branchId);

            if (!$payload) {
                $this->lastError = 'Branch is missing its agency, plan or owner.';

                return false;
            }

            $this->post('/api/sandbox/provision', $payload);

            return true;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();

            Log::error('Sandbox provisioning failed', [
                'branch_id' => $branchId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function syncLock(Subscription $subscription): void
    {
        if (!$this->enabled()) {
            return;
        }

        $agencyUuid = $subscription->agency()->value('uuid');

        if (!$agencyUuid) {
            return;
        }

        $state = self::demoState($subscription);

        DB::afterCommit(function () use ($agencyUuid, $state) {
            try {
                $this->post('/api/sandbox/lock', [
                    'agency_uuid' => $agencyUuid,
                    'state' => $state,
                ]);
            } catch (\Throwable $e) {
                Log::error('Sandbox lock sync failed', [
                    'agency_uuid' => $agencyUuid,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    public static function demoState(?Subscription $subscription): string
    {
        return match (true) {
            !$subscription => 'open',
            $subscription->isLockedForPayment() => 'ended',
            $subscription->status === Subscription::STATUS_PENDING => 'review',
            default => 'open',
        };
    }

    private function post(string $path, array $payload): void
    {
        Http::timeout(15)
            ->acceptJson()
            ->withHeaders(['X-Sandbox-Secret' => config('services.sandbox.secret')])
            ->post(rtrim(config('services.sandbox.url'), '/') . $path, $payload)
            ->throw();
    }

    private function payload(int $branchId): ?array
    {
        $branch = Branch::with([
            'location',
            'agencies.locations',
            'subscriptionLink.subscription.plans',
        ])->find($branchId);

        $agency = $branch?->agencies;
        $plan = $branch?->subscriptionLink?->subscription?->plans;
        $owner = $agency ? User::with('employee')->find($agency->registered_by) : null;

        if (!$branch || !$agency || !$plan || !$owner) {
            return null;
        }

        return [
            'owner' => [
                'email' => $owner->email,
                'password' => $owner->getAuthPassword(),
                'provider' => $owner->provider,
                'first_name' => $owner->employee?->first_name,
                'last_name' => $owner->employee?->last_name,
                'avatar' => $owner->employee?->avatar,
            ],
            'agency' => [
                'uuid' => $agency->uuid,
                'name' => $agency->name,
                'description' => $agency->description,
                'email' => $agency->email,
                'image' => $agency->image,
                'location' => $this->location($agency->locations),
            ],
            'branch' => [
                'uuid' => $branch->uuid,
                'name' => $branch->name,
                'description' => $branch->description,
                'email' => $branch->email,
                'contact_number' => $branch->contact_number,
                'image' => $branch->image,
                'settings' => $branch->settings,
                'location' => $this->location($branch->location),
            ],
            'plan' => [
                'plan_code' => $plan->plan_code,
                'type' => $plan->type,
            ],
            'state' => self::demoState($branch->subscriptionLink?->subscription),
        ];
    }

    private function location($location): ?array
    {
        return $location ? [
            'street' => $location->street,
            'city' => $location->city,
            'province' => $location->province,
            'country' => $location->country,
            'full_address' => $location->full_address,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
        ] : null;
    }
}
