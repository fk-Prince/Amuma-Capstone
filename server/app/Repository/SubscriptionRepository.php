<?php

namespace App\Repository;

use App\Models\Agency;
use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use Carbon\Carbon;
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

    /**
     * A branch can accumulate several subscription rows over time, so renewal
     * has to act on the newest one rather than whatever the table returns first.
     */
    public function findLatestForBranch(string $branchId)
    {
        return Subscription::with('plans')
            ->whereHas('branchLinks', fn($q) => $q->where('branch_id', $branchId))
            ->latest('created_at')
            ->first();
    }

    /**
     * Returns the agency's oldest subscription that still has a free slot, so
     * a new branch joins an already-paid period instead of starting its own.
     */
    public function findSubscriptionWithRoom(string $agencyId, ?string $subscriptionUuid = null)
    {
        return Subscription::query()
            ->where('agency_id', $agencyId)
            // Scoped when the owner picked which of their subscriptions the
            // branch should join; still bound to their own agency either way.
            ->when($subscriptionUuid, fn($q) => $q->where('uuid', $subscriptionUuid))
            ->where('status', '!=', Subscription::STATUS_REJECTED)
            ->whereHas('payments', fn($q) => $q->where('status', SubscriptionPayment::STATUS_PAID))
            ->whereRaw(
                '(select count(*) from branch_subscription bs
                    where bs.subscription_id = subscriptions.subscription_id
                      and bs.status != ?) < ?',
                [BranchSubscription::STATUS_REJECTED, Subscription::BRANCH_LIMIT]
            )
            ->orderBy('created_at')
            ->lockForUpdate()
            ->first();
    }

    /**
     * The approval queue is per branch, not per subscription — one paid
     * subscription can carry up to five branches, each reviewed on its own.
     */
    public function paginate(array $payload)
    {
        $query = BranchSubscription::query()
            ->with([
                'branch.agencies',
                'subscription.plans',
                'subscription.payments',
            ]);

        if (!empty($payload['branch_id'])) {
            $query->where('branch_id', $payload['branch_id']);
        }

        if (!empty($payload['status'])) {
            $status = $payload['status'];

            // The requests tab filters on the branch's own review state; the
            // approved tab filters on the paid period's lifecycle instead.
            if (in_array($status, [
                BranchSubscription::STATUS_PENDING,
                BranchSubscription::STATUS_APPROVED,
                BranchSubscription::STATUS_REJECTED,
            ], true)) {
                $query->where('status', $status);
            } else {
                $query->where('status', '!=', BranchSubscription::STATUS_REJECTED)
                    ->whereHas('subscription', fn($q) => $q->where('status', $status));
            }
        }

        if (!empty($payload['search'])) {
            $search = trim($payload['search']);

            $query->where(function ($builder) use ($search) {
                $builder
                    ->whereHas('branch', function ($branchQuery) use ($search) {
                        $branchQuery->where('name', 'ilike', "%{$search}%");
                    })
                    ->orWhereHas('branch.agencies', function ($agencyQuery) use ($search) {
                        $agencyQuery->where('name', 'ilike', "%{$search}%");
                    });
            });
        }

        return $query
            ->latest('created_at')
            ->paginate($payload['per_page'] ?? 15);
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

    public function overview(int $revenueMonths = 6, ?int $revenueYear = null)
    {
        $revenueMonths = max(1, min($revenueMonths, 24));

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

        if ($revenueYear) {
            $revenueByMonthRaw = SubscriptionPayment::query()
                ->where('status', 'paid')
                ->whereYear('created_at', $revenueYear)
                ->selectRaw("TO_CHAR(created_at, 'YYYY-MM') as month")
                ->selectRaw('SUM(price) as total')
                ->groupBy('month')
                ->pluck('total', 'month');

            $revenueByMonth = collect(range(0, 11))->map(function (int $month) use ($revenueByMonthRaw, $revenueYear) {
                $date = Carbon::create($revenueYear, $month + 1, 1);

                return [
                    'month' => $date->format('M'),
                    'total' => (float) ($revenueByMonthRaw[$date->format('Y-m')] ?? 0),
                ];
            })->values();
        } else {
            $revenueByMonthRaw = SubscriptionPayment::query()
                ->where('status', 'paid')
                ->where('created_at', '>=', Carbon::now()->subMonths($revenueMonths - 1)->startOfMonth())
                ->selectRaw("TO_CHAR(created_at, 'YYYY-MM') as month")
                ->selectRaw('SUM(price) as total')
                ->groupBy('month')
                ->pluck('total', 'month');

            $revenueByMonth = collect(range($revenueMonths - 1, 0))->map(function (int $monthsAgo) use ($revenueByMonthRaw) {
                $date = Carbon::now()->subMonths($monthsAgo)->startOfMonth();

                return [
                    'month' => $date->format('M Y'),
                    'total' => (float) ($revenueByMonthRaw[$date->format('Y-m')] ?? 0),
                ];
            })->values();
        }

        $earliestPaymentAt = SubscriptionPayment::query()
            ->where('status', 'paid')
            ->min('created_at');

        $earliestYear = $earliestPaymentAt
            ? Carbon::parse($earliestPaymentAt)->year
            : Carbon::now()->year;

        $availableYears = collect(range(Carbon::now()->year, $earliestYear))->values();

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
                'revenue_by_month' => $revenueByMonth,
                'available_revenue_years' => $availableYears,
                'recent' => $recent,
            ],
        ];
    }
}
