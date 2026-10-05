<?php

namespace App\Repository;

use App\Models\Agency;
use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\VerificationLog;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class SubscriptionRepository
{
    public function paymentsByUser(mixed $userId)
    {
        return SubscriptionPayment::with(['plan:plan_id,name', 'branch:branch_id,name'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(array $payload)
    {
        return Subscription::create($payload);
    }

    public function findDueForRenewalReminder(int $days)
    {
        return Subscription::query()
            ->with(['plans', 'pendingPlan', 'branchLinks.branch'])
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_EXPIRED])
            ->whereNull('pending_plan_id')
            ->whereDate('end_date', '<=', Carbon::today()->addDays($days))
            ->get();
    }

    public function agencyHasUsedTrial(?int $agencyId): bool
    {
        if (!$agencyId) {
            return false;
        }

        return Subscription::where('agency_id', $agencyId)
            ->where('status', '!=', Subscription::STATUS_REJECTED)
            ->exists();
    }

    public function findByFields(array $payload)
    {
        return Subscription::where($payload)->first();
    }


    public function findPaymentByReference(string $reference): ?SubscriptionPayment
    {
        return SubscriptionPayment::with('plan', 'subscription.agency')
            ->where('payment_reference_id', $reference)
            ->first();
    }

    public function agencyIsTesting(int|string|null $agencyId): bool
    {
        if (!$agencyId) {
            return false;
        }

        return Subscription::where('agency_id', $agencyId)
            ->where('mode', Subscription::MODE_TEST)
            ->whereIn('status', [Subscription::STATUS_PENDING, Subscription::STATUS_ACTIVE])
            ->exists();
    }

    public function agencyHasSubscription(int|string $agencyId): bool
    {
        return Subscription::where('agency_id', $agencyId)
            ->where('status', '!=', Subscription::STATUS_REJECTED)
            ->exists();
    }

    public function findForAgency(int|string $agencyId, string $uuid, bool $lock = false)
    {
        return Subscription::with('plans')
            ->where('agency_id', $agencyId)
            ->where('uuid', $uuid)
            ->when($lock, fn($q) => $q->lockForUpdate())
            ->first();
    }

    public function findLatestForBranch(string $branchId)
    {
        return Subscription::with(['plans', 'latestPayment'])
            ->whereHas('branchLinks', fn($q) => $q->where('branch_id', $branchId))
            ->latest('created_at')
            ->first();
    }


    public function findSubscriptionWithRoom(string $agencyId, ?string $subscriptionUuid = null)
    {
        return Subscription::query()
            ->where('agency_id', $agencyId)
            ->when($subscriptionUuid, fn($q) => $q->where('uuid', $subscriptionUuid))
            ->whereIn('status', [Subscription::STATUS_PENDING, Subscription::STATUS_ACTIVE])
            ->whereHas('payments', fn($q) => $q->where('status', SubscriptionPayment::STATUS_PAID))
            ->withOpenSlot()
            ->orderBy('created_at')
            ->lockForUpdate()
            ->first();
    }


    public function paginate(array $payload)
    {
        $filtered = $this->filteredBranchSubscriptionQuery($payload);

        // Pending requests are reviewed and actioned one branch at a time,
        // so every pending branch needs its own card. Approved/rejected
        // views instead collapse a subscription's branches into one card
        // (with a branch switcher), so only those views dedupe.
        if (($payload['status'] ?? null) !== BranchSubscription::STATUS_PENDING) {
            $representativeIds = (clone $filtered)
                ->selectRaw('MIN(branch_subscription_id) as branch_subscription_id')
                ->groupBy('subscription_id')
                ->pluck('branch_subscription_id');

            $filtered = BranchSubscription::query()
                ->whereIn('branch_subscription_id', $representativeIds);
        }

        return $filtered
            ->with([
                'latestRejection',
                'branch' => fn($query) => $query->withCount([
                    'verificationLogs as rejection_logs_count' => fn($logs) => $logs
                        ->where('verification_logs.action', VerificationLog::ACTION_REJECTED),
                ]),
                'branch.agencies',
                'subscription.plans',
                'subscription.pendingPlan',
                'subscription.payments.plan',
                'subscription.payments.branch',
                'subscription.latestPayment',
            ])
            ->latest('created_at')
            ->paginate($payload['per_page'] ?? 15);
    }

    private function filteredBranchSubscriptionQuery(array $payload)
    {
        $query = BranchSubscription::query();

        if (!empty($payload['branch_id'])) {
            $query->where('branch_id', $payload['branch_id']);
        }

        if (!empty($payload['status'])) {
            $status = $payload['status'];

            if (in_array($status, [
                BranchSubscription::STATUS_PENDING,
                BranchSubscription::STATUS_APPROVED,
                BranchSubscription::STATUS_REJECTED,
            ], true)) {
                $query->where('status', $status);
            } elseif ($status === 'expiring') {
                $query->where('status', '!=', BranchSubscription::STATUS_REJECTED)
                    ->whereHas('subscription', fn($q) => $this->expiringSoon($q));
            } else {
                $query->where('status', '!=', BranchSubscription::STATUS_REJECTED)
                    ->whereHas('subscription', fn($q) => $q->where('status', $status));
            }
        }

        if (!empty($payload['plan_code'])) {
            $query->whereHas(
                'subscription',
                fn($q) => $q->whereHas(
                    'plans',
                    fn($planQuery) => $planQuery->where('plan_code', $payload['plan_code'])
                )
            );
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

        return $query;
    }

    private function expiringSoon($query)
    {
        return $query->where('status', Subscription::STATUS_ACTIVE)
            ->where('end_date', '>=', today())
            ->where('end_date', '<', today()->addWeek());
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
            'expired' => (int) ($counts['expired'] ?? 0),
            'expiring_soon' => $this->expiringSoon(Subscription::query())->count(),
            'rejected' => BranchSubscription::where('status', BranchSubscription::STATUS_REJECTED)->count(),
            'active_branches' => BranchSubscription::where(
                'status',
                BranchSubscription::STATUS_APPROVED
            )
                ->whereHas(
                    'subscription',
                    fn($query) => $query->where('status', Subscription::STATUS_ACTIVE)
                )
                ->count(),
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
            Subscription::STATUS_REJECTED,
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
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'verified') as verified")
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'rejected') as rejected")
            ->first();

        $agencyTotals = Agency::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'verified') as verified")
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'rejected') as rejected")
            ->first();

        $planBreakdown = Plan::query()
            ->select(
                'plans.name',
                'plans.plan_code',
                'plans.type'
            )
            ->selectRaw('COUNT(subscriptions.subscription_id) as total')            ->leftJoin(
                'subscriptions',
                function ($join) {
                    $join->on('subscriptions.plan_id', '=', 'plans.plan_id')
                        ->where('subscriptions.status', '!=', Subscription::STATUS_REJECTED);
                }
            )
            ->groupBy(
                'plans.plan_id',
                'plans.name',
                'plans.plan_code',
                'plans.type'
            )
            ->orderBy('plans.plan_code', 'asc')
            ->orderBy('plans.type', 'desc')
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

        $recent = BranchSubscription::query()
            ->with([
                'latestRejection',
                'branch.agencies',
                'subscription.plans',
                'subscription.payments.plan',
                'subscription.latestPayment',
            ])
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn(BranchSubscription $link) => (new SubscriptionResource($link))->resolve());

        return [
            'data' => [
                'total' => $total,
                'by_status' => $byStatus,
                'branches' => [
                    'total' => (int) ($branchTotals->total ?? 0),
                    'verified' => (int) ($branchTotals->verified ?? 0),
                    'rejected' => (int) ($branchTotals->rejected ?? 0),
                ],
                'agencies' => [
                    'total' => (int) ($agencyTotals->total ?? 0),
                    'verified' => (int) ($agencyTotals->verified ?? 0),
                    'rejected' => (int) ($agencyTotals->rejected ?? 0),
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
