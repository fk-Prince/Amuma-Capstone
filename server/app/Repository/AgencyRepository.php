<?php

namespace App\Repository;

use App\Models\Agency;
use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Carbon\Carbon;

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
            ->where('status', Branch::STATUS_VERIFIED)
            ->count();

        $newThisMonth = Branch::query()
            ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $expiringSoon = Subscription::query()
            ->where('status', 'active')
            ->whereBetween('end_date', [
                today(),
                today()->addDays(Subscription::RENEWAL_WINDOW_DAYS),
            ])
            ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId))
            ->count();

        $nearestEnd = Subscription::query()
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_PENDING])
            ->whereDate('end_date', '>=', today())
            ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId))
            ->min('end_date');

        $maintenanceAlerts = Subscription::query()
            ->where('status', 'active')
            ->where('end_date', '<', today())
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
                'subscription_end_date' => $nearestEnd
                    ? Carbon::parse($nearestEnd)->toDateString()
                    : null,
                'expires_in_days' => $nearestEnd
                    ? (int) today()->diffInDays(Carbon::parse($nearestEnd)->startOfDay())
                    : null,
                'expiring_soon' => $expiringSoon,
                'expiring_soon_percent' => $totalBranches
                    ? round(($expiringSoon / $totalBranches) * 100)
                    : 0,
                'maintenance_alerts' => $maintenanceAlerts,
                'branch_capacity' => $this->branchCapacity($agencyId),
            ],
        ]);
    }


    public function branchCapacity(?string $agencyId): array
    {
        if (!$agencyId) {
            return [
                'used' => 0,
                'capacity' => 0,
                'remaining' => 0,
                'has_room' => false,
                'available_subscriptions' => [],
                'additional_options' => [],
                'is_testing' => false,
            ];
        }

        $capacity = Subscription::query()
            ->with('plans')
            ->where('agency_id', $agencyId)
            ->whereNotIn('status', [Subscription::STATUS_REJECTED, Subscription::STATUS_CANCELLED])
            ->whereHas('payments', fn($q) => $q->where('status', SubscriptionPayment::STATUS_PAID))
            ->get()
            ->sum(fn($subscription) => $subscription->branchLimit() + $subscription->additionalBranchCount());

        $used = Branch::query()
            ->where('agency_id', $agencyId)
            ->where('status', '!=', Branch::STATUS_REJECTED)
            ->count();

        $additionalOptions = Subscription::query()
            ->with('plans')
            ->where('agency_id', $agencyId)
            ->orderBy('created_at')
            ->get()
            ->filter(fn($subscription) => $subscription->canAddAdditionalBranch())
            ->map(fn($subscription) => [
                'uuid' => $subscription->uuid,
                'plan_name' => $subscription->plans?->name,
                'plan_code' => $subscription->plans?->plan_code,
                'plan_type' => $subscription->plans?->type,
                'end_date' => $subscription->end_date,
                ...$subscription->additionalBranchQuote(),
            ])
            ->values();

        $available = Subscription::query()
            ->with(['plans', 'latestPayment'])
            ->withCount([
                'branchLinks as branches_used' => fn($q) => $q
                    ->where('status', '!=', BranchSubscription::STATUS_REJECTED)
                    ->where('type', BranchSubscription::TYPE_INCLUDED),
            ])
            ->where('agency_id', $agencyId)
            ->whereIn('status', [Subscription::STATUS_PENDING, Subscription::STATUS_ACTIVE])
            ->whereHas('payments', fn($q) => $q->where('status', SubscriptionPayment::STATUS_PAID))
            ->withOpenSlot()
            ->orderBy('created_at')
            ->get()
            ->map(fn($subscription) => [
                'uuid' => $subscription->uuid,
                'plan_name' => $subscription->plans?->name,
                'plan_code' => $subscription->plans?->plan_code,
                'plan_type' => $subscription->plans?->type,
                'status' => $subscription->status,
                'end_date' => $subscription->end_date,
                'branches_used' => (int) $subscription->branches_used,
                'branch_limit' => $subscription->branchLimit(),
                'slots_left' => $subscription->branchLimit() - (int) $subscription->branches_used,
            ])
            ->values();

        return [
            'used' => $used,
            'capacity' => $capacity,
            'remaining' => max(0, $capacity - $used),
            'has_room' => $used < $capacity,
            'available_subscriptions' => $available,
            'additional_options' => $additionalOptions,
            'is_testing' => app(SubscriptionRepository::class)->agencyIsTesting($agencyId),
        ];
    }
    public function paginate(array $payload)
    {
        $agencyId = $payload['agency_id'] ?? null;
        $search = $payload['search'] ?? null;
        $status = $payload['status'] ?? null;
        $perPage = $payload['per_page'] ?? 12;

        $branches = Branch::query()
            ->with([
                'location',
                'agencies',
                'subscriptionLink.subscription' => fn($query) => $query
                    ->withCount([
                        'branchLinks as branches_used' => fn($links) => $links
                            ->where('status', '!=', BranchSubscription::STATUS_REJECTED)
                            ->where('type', BranchSubscription::TYPE_INCLUDED),
                    ])
                    ->withExists([
                        'payments as has_paid_payment' => fn($payments) => $payments
                            ->where('status', SubscriptionPayment::STATUS_PAID),
                    ]),
                'subscriptionLink.subscription.plans',
                'subscriptionLink.latestRejection',
            ])
            ->withCount(['patients', 'employees'])
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
            ->when(
                $status && $status !== 'all',
                fn($q) => $q->where('status', $status)
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
                'status' => $branch->status,
                'review_status' => $branch->status,
                'rejection_reason' => $branch->subscriptionLink?->rejection_reason,
                'subscription_status' => $branch->subscriptionLink?->subscription?->status,
                'slot_available' => (bool) $branch->subscriptionLink?->subscription?->hasOpenSlot(),
                'contact_number' => $branch->contact_number,
                'email' => $branch->email,
                'tin' => data_get($branch->settings, 'tin'),
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
                'staff_count' => $branch->employees_count,
                'patients_count' => $branch->patients_count,
                'plan' => $branch->subscriptionLink?->subscription?->effectivePlan()
                    ? [
                        'plan_code' => $branch->subscriptionLink->subscription->effectivePlan()->plan_code,
                        'name' => $branch->subscriptionLink->subscription->effectivePlan()->name,
                    ]
                    : null,
            ];
        });
        return $branches;
    }
}
