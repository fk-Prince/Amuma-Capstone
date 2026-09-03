<?php

namespace App\Repository;

use App\Models\Agency;
use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;

class AgencyRepository
{

    public function createAgency(array $payload)
    {
        return Agency::create($payload);
    }


    public function findAgencyByField(string $column, string $value)
    {
        return Agency::where($column, $value)->first();
    }

    public function stats(string $agencyId)
    {
        $totalBranches = Branch::query()
            ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId))
            ->count();

        $activeBranches = Branch::query()
            ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId))
            ->where('is_verified', true)
            ->count();

        $newThisMonth = Branch::query()
            ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $expiringSoon = Subscription::query()
            ->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays(14)])
            ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId))
            ->count();

        $maintenanceAlerts = Subscription::query()
            ->where('status', 'active')
            ->where('end_date', '<', now())
            ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId))
            ->count();

        return response()->json([
            'data' => [
                'total_branches' => $totalBranches,
                'total_branches_new_this_month' => $newThisMonth,
                'active_branches' => $activeBranches,
                'active_branches_percent' => $totalBranches
                    ? round(($activeBranches / $totalBranches) * 100)
                    : 0,
                'expiring_soon' => $expiringSoon,
                'expiring_soon_percent' => $totalBranches
                    ? round(($expiringSoon / $totalBranches) * 100)
                    : 0,
                'maintenance_alerts' => $maintenanceAlerts,
                'branch_capacity' => $this->branchCapacity($agencyId),
            ],
        ]);
    }

    public const BRANCHES_PER_SUBSCRIPTION = 5;

    public function branchCapacity(?string $agencyId): array
    {
        if (!$agencyId) {
            return [
                'used' => 0,
                'capacity' => 0,
                'remaining' => 0,
                'has_room' => false,
                'available_subscriptions' => [],
            ];
        }

        $paidSubscriptions = Subscription::query()
            ->where('agency_id', $agencyId)
            ->where('status', '!=', Subscription::STATUS_REJECTED)
            ->whereHas('payments', fn($q) => $q->where('status', SubscriptionPayment::STATUS_PAID))
            ->count();

        $capacity = self::BRANCHES_PER_SUBSCRIPTION * $paidSubscriptions;

        $used = BranchSubscription::query()
            ->where('status', '!=', BranchSubscription::STATUS_REJECTED)
            ->whereHas(
                'subscription',
                fn($q) => $q->where('agency_id', $agencyId)
                    ->where('status', '!=', Subscription::STATUS_REJECTED)
            )
            ->count();

        $available = Subscription::query()
            ->with('plans')
            ->withCount([
                'branchLinks as branches_used' => fn($q) => $q->where(
                    'status',
                    '!=',
                    BranchSubscription::STATUS_REJECTED
                ),
            ])
            ->where('agency_id', $agencyId)
            ->where('status', '!=', Subscription::STATUS_REJECTED)
            ->whereHas('payments', fn($q) => $q->where('status', SubscriptionPayment::STATUS_PAID))
            ->whereRaw(
                '(select count(*) from branch_subscription bs
                    where bs.subscription_id = subscriptions.subscription_id
                      and bs.status != ?) < ?',
                [BranchSubscription::STATUS_REJECTED, Subscription::BRANCH_LIMIT]
            )
            ->orderBy('created_at')
            ->get()
            ->map(fn($subscription) => [
                'uuid' => $subscription->uuid,
                'plan_name' => $subscription->plans?->name,
                'plan_code' => $subscription->plans?->plan_code,
                'billing_interval' => $subscription->billing_interval,
                'status' => $subscription->status,
                'end_date' => $subscription->end_date,
                'branches_used' => (int) $subscription->branches_used,
                'branch_limit' => Subscription::BRANCH_LIMIT,
                'slots_left' => Subscription::BRANCH_LIMIT - (int) $subscription->branches_used,
            ])
            ->values();

        return [
            'used' => $used,
            'capacity' => $capacity,
            'remaining' => max(0, $capacity - $used),
            'has_room' => $used < $capacity,
            'available_subscriptions' => $available,
        ];
    }
    public function paginate(array $payload)
    {
        $agencyId = $payload['agency_id'] ?? null;
        $search = $payload['search'] ?? null;
        $status = $payload['status'] ?? null;
        $perPage = $payload['per_page'] ?? 12;

        $branches = Branch::query()
            ->with(['location', 'agencies', 'subscriptionLink'])
            ->withCount(['rooms', 'patients', 'employees'])
            ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId))
            // ilike, not like: Postgres LIKE is case-sensitive, so a lowercase
            // query would never match a capitalised branch or city name.
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%")
                        ->orWhere('contact_number', 'ilike', "%{$search}%")
                        ->orWhereHas('location', function ($loc) use ($search) {
                            $loc->where('city', 'ilike', "%{$search}%")
                                ->orWhere('street', 'ilike', "%{$search}%")
                                ->orWhere('province', 'ilike', "%{$search}%")
                                ->orWhere('country', 'ilike', "%{$search}%");
                        });
                });
            })
            ->when($status === 'rejected', fn($q) => $q->whereHas(
                'subscriptionLink',
                fn($link) => $link->where('status', BranchSubscription::STATUS_REJECTED)
            ))
            ->when(
                $status && !in_array($status, ['all', 'rejected'], true),
                fn($q) => $q->where('is_verified', $status === 'verified')
                    ->whereDoesntHave(
                        'subscriptionLink',
                        fn($link) => $link->where('status', BranchSubscription::STATUS_REJECTED)
                    )
            )
            ->latest('created_at')
            ->paginate($perPage);

        $branches->getCollection()->transform(function ($branch) {
            return [
                'branch_id' => $branch->branch_id,
                'uuid' => $branch->uuid,
                'name' => $branch->name,
                'description' => $branch->description,
                'image' => $branch->image,
                'is_verified' => $branch->is_verified,
                'review_status' => $branch->subscriptionLink?->status === BranchSubscription::STATUS_REJECTED
                    ? 'rejected'
                    : ($branch->is_verified ? 'verified' : 'pending'),
                'contact_number' => $branch->contact_number,
                'email' => $branch->email,
                'location' => $branch->location ? [
                    'street' => $branch->location->street,
                    'city' => $branch->location->city,
                    'province' => $branch->location->province,
                    'country' => $branch->location->country,
                    'full_address' => $branch->location->full_address,
                ] : null,
                'agency' => $branch->agencies ? [
                    'agency_id' => $branch->agencies->agency_id,
                    'name' => $branch->agencies->name,
                ] : null,
                'rooms_count' => $branch->rooms_count,
                'staff_count' => $branch->employees_count,
                'patients_count' => $branch->patients_count,
            ];
        });
        return $branches;
    }
}
