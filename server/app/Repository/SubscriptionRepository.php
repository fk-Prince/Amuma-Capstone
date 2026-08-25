<?php

namespace App\Repository;

use App\Models\Agency;
use App\Models\Branch;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class SubscriptionRepository
{


    public function create(array $payload)
    {
        return Subscription::create($payload);
    }

    public function findByFields(array $payload)
    {
        return Subscription::where($payload)->first();
    }

    public function paginate(array $payload)
    {
        $query = Subscription::query()
            ->with([
                'branch.agencies',
                'plans',
                'payments',
            ]);

        if (!empty($payload['status'])) {
            $query->where('status', $payload['status']);
        }

        if (!empty($payload['search'])) {
            $search = trim($payload['search']);

            $query->where(function ($builder) use ($search) {
                $builder
                    ->whereHas('branch', function ($branchQuery) use ($search) {
                        $branchQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('branch.agencies', function ($agencyQuery) use ($search) {
                        $agencyQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->paginate($payload['per_page'] ?? 15);
    }

    public function overviewSubscription()
    {
        $counts = Subscription::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending' => (int) ($counts['pending'] ?? 0),
            'active' => (int) ($counts['active'] ?? 0),
            'inactive' => (int) ($counts['inactive'] ?? 0),
            'expired' => (int) ($counts['expired'] ?? 0),
        ];
    }

    public function overview()
    {
        $statusCounts = Subscription::query()
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = [
            Subscription::STATUS_PENDING,
            Subscription::STATUS_ACTIVE,
            Subscription::STATUS_INACTIVE,
            Subscription::STATUS_EXPIRED,
        ];

        $byStatus = collect($statuses)->mapWithKeys(
            fn($status) => [
                $status => (int) ($statusCounts[$status] ?? 0),
            ]
        );

        $total = $byStatus->sum();

        $branchTotals = Branch::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COUNT(*) FILTER (WHERE is_verified = true) as verified')
            ->first();

        $agencyTotals = Agency::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COUNT(*) FILTER (WHERE is_verified = true) as verified')
            ->first();

        $planBreakdown = Plan::query()
            ->select(
                'plans.name',
                'plans.plan_code'
            )
            ->selectRaw('COUNT(subscriptions.subscription_id) as total')
            ->leftJoin(
                'subscriptions',
                'subscriptions.plan_id',
                '=',
                'plans.plan_id'
            )
            ->groupBy(
                'plans.plan_id',
                'plans.name',
                'plans.plan_code'
            )
            ->orderBy('plans.plan_code', 'asc')
            ->get();


        $revenue = SubscriptionPayment::query()
            ->where('status', 'paid')
            ->sum('price');

        $paidPaymentsCount = SubscriptionPayment::query()
            ->where('status', 'paid')
            ->count();

        $recent = Subscription::query()
            ->with([
                'branch.agencies',
                'plans',
            ])
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn(Subscription $subscription) => (new SubscriptionResource($subscription))->resolve());

        return [
            'data' => [
                'total' => $total,
                'by_status' => $byStatus,
                'branches' => [
                    'total' => (int) ($branchTotals->total ?? 0),
                    'verified' => (int) ($branchTotals->verified ?? 0),
                ],
                'agencies' => [
                    'total' => (int) ($agencyTotals->total ?? 0),
                    'verified' => (int) ($agencyTotals->verified ?? 0),
                ],
                'plan_breakdown' => $planBreakdown,
                'revenue_total' => (float) $revenue,
                'paid_payments_count' => $paidPaymentsCount,
                'recent' => $recent,
            ],
        ];
    }
}
