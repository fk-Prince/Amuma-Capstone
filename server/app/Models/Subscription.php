<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasUuids;
    protected $primaryKey = 'subscription_id';

    public const RENEWAL_WINDOW_DAYS = 7;
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CANCELLED = 'cancelled';
    public const MODE_TEST = 'test';
    public const MODE_LIVE = 'live';
    public const TEST_MONTHS = 1;
    public const TERM_MONTHS = 12;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'pending_plan_starts_at' => 'date',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    protected $fillable = [
        'plan_id',
        'pending_plan_id',
        'pending_plan_starts_at',
        'agency_id',
        'status',
        'mode',
        'start_date',
        'end_date',
    ];

    public function plans()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'plan_id',);
    }

    public function pendingPlan()
    {
        return $this->belongsTo(Plan::class, 'pending_plan_id', 'plan_id');
    }

    public function effectivePlan()
    {
        return $this->pendingPlanIsDue() ? $this->pendingPlan : $this->plans;
    }

    public function pendingPlanIsDue()
    {
        return $this->pending_plan_id
            && $this->pending_plan_starts_at
            && !now()->startOfDay()->lt($this->pending_plan_starts_at);
    }

    public function pendingPlanSummary(): ?array
    {
        if (!$this->pending_plan_id) {
            return null;
        }

        return [
            'name' => $this->pendingPlan?->name,
            'plan_code' => $this->pendingPlan?->plan_code,
            'type' => $this->pendingPlan?->type,
            'branch_limit' => $this->pendingPlan?->branch_limit,
            'starts_at' => $this->pending_plan_starts_at?->toDateString(),
            'ends_at' => $this->pending_plan_starts_at
                ? self::termEnd($this->pending_plan_starts_at)->toDateString()
                : null,
            'is_due' => $this->pendingPlanIsDue(),
        ];
    }

    public function pendingPlanChanges(?Carbon $startsAt = null): array
    {
        $startsAt = $startsAt ?? $this->pending_plan_starts_at->copy();

        return [
            'mode' => self::MODE_LIVE,
            'plan_id' => $this->pending_plan_id,
            'start_date' => $startsAt,
            'end_date' => self::termEnd($startsAt),
            'pending_plan_id' => null,
            'pending_plan_starts_at' => null,
        ];
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id', 'agency_id');
    }



    public function branches()
    {
        return $this->belongsToMany(
            Branch::class,
            'branch_subscription',
            'subscription_id',
            'branch_id'
        )
            ->using(BranchSubscription::class)
            ->withPivot(['branch_subscription_id', 'uuid', 'status'])
            ->withTimestamps();
    }

    public function branchLinks()
    {
        return $this->hasMany(BranchSubscription::class, 'subscription_id', 'subscription_id');
    }

    public function hasOpenSlot(): bool
    {
        if (in_array($this->status, [self::STATUS_REJECTED, self::STATUS_EXPIRED, self::STATUS_CANCELLED], true)) {
            return false;
        }

        $hasPaid = $this->has_paid_payment
            ?? $this->payments()->where('status', SubscriptionPayment::STATUS_PAID)->exists();

        $used = $this->branches_used
            ?? $this->branchLinks()
            ->where('status', '!=', BranchSubscription::STATUS_REJECTED)
            ->where('type', BranchSubscription::TYPE_INCLUDED)
            ->count();

        return $hasPaid && (int) $used < $this->branchLimit();
    }

    public function additionalBranchCount(): int
    {
        return $this->branchLinks()
            ->where('status', '!=', BranchSubscription::STATUS_REJECTED)
            ->where('type', BranchSubscription::TYPE_ADDITIONAL)
            ->count();
    }

    public function renewalAmount(Plan $plan, ?int $additionalBranches = null): float
    {
        $additionalBranches ??= $this->additionalBranchCount();

        return round((float) $plan->price + $additionalBranches * (float) $plan->additional_branch_price, 2);
    }

    public function canAddAdditionalBranch(): bool
    {
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_ACTIVE], true)) {
            return false;
        }

        $hasPaid = $this->payments()->where('status', SubscriptionPayment::STATUS_PAID)->exists();

        return $hasPaid && !$this->isTest() && !$this->hasOpenSlot() && $this->upgradeMonths() > 0;
    }

    public function additionalBranchQuote(): array
    {
        $months = $this->upgradeMonths();
        $unit = (float) ($this->plans?->additional_branch_price ?? 0);

        return [
            'additional_branch_price' => $unit,
            'months' => $months,
            'amount' => self::proratedAdditionalBranch($unit, $months),
        ];
    }

    public static function proratedAdditionalBranch(float $unitPrice, int $months): float
    {
        return round($unitPrice / self::TERM_MONTHS * $months, 2);
    }

    public function branchLimit(): int
    {
        return $this->plans?->branch_limit ?? Plan::branchLimitFor(null);
    }

    public function scopeWithOpenSlot(mixed $query)
    {
        return $query->whereRaw(
            '(select count(*) from branch_subscription bs
                where bs.subscription_id = subscriptions.subscription_id
                  and bs.type = ?
                  and bs.status != ?) < (select ' . Plan::branchLimitSql('p.type') . ' from plans p
                where p.plan_id = subscriptions.plan_id)',
            [BranchSubscription::TYPE_INCLUDED, BranchSubscription::STATUS_REJECTED]
        );
    }

    public function planSummary(): array
    {
        return [
            'plan_id' => $this->plans?->plan_id,
            'name' => $this->plans?->name,
            'plan_code' => $this->plans?->plan_code,
            'type' => $this->plans?->type,
            'branch_limit' => $this->branchLimit(),
            'price' => (float) $this->plans?->price,
            'additional_branch_price' => (float) $this->plans?->additional_branch_price,
            'additional_branches' => $this->additionalBranchCount(),
            'yearly_total' => $this->plans ? $this->renewalAmount($this->plans) : 0.0,
        ];
    }


    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class, 'subscription_id', 'subscription_id');
    }

    public function latestPayment()
    {
        return $this->hasOne(SubscriptionPayment::class, 'subscription_id', 'subscription_id')
            ->latestOfMany('subscription_payment_id');
    }

    public function daysLeft(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->end_date->copy()->startOfDay(), false);
    }

    public function renewalOpensAt()
    {
        return $this->end_date->copy()->subDays(self::RENEWAL_WINDOW_DAYS);
    }

    public function isTest(): bool
    {
        return $this->mode === self::MODE_TEST;
    }

    public static function termEnd(mixed $from): Carbon
    {
        return Carbon::parse($from)->copy()->addYear();
    }

    public static function liveTerms(int $planId, ?Carbon $startsAt = null): array
    {
        $startsAt = $startsAt ?? Carbon::now();

        return [
            'mode' => self::MODE_LIVE,
            'plan_id' => $planId,
            'start_date' => $startsAt,
            'end_date' => self::termEnd($startsAt),
            'pending_plan_id' => null,
            'pending_plan_starts_at' => null,
        ];
    }

    public static function newTerms(int $planId, bool $withTrial, ?Carbon $startsAt = null): array
    {
        return $withTrial
            ? self::testTerms($planId, $startsAt)
            : self::liveTerms($planId, $startsAt);
    }

    public static function testTerms(int $planId, ?Carbon $startsAt = null): array
    {
        $startsAt = $startsAt ?? Carbon::now();
        $testEnds = $startsAt->copy()->addMonths(self::TEST_MONTHS);

        return [
            'mode' => self::MODE_TEST,
            'plan_id' => $planId,
            'start_date' => $startsAt,
            'end_date' => $testEnds,
            'pending_plan_id' => $planId,
            'pending_plan_starts_at' => $testEnds,
        ];
    }

    public function canCancelTest(): bool
    {
        return $this->isTest()
            && in_array($this->status, [self::STATUS_PENDING, self::STATUS_ACTIVE], true)
            && $this->pending_plan_id
            && !$this->pendingPlanIsDue();
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isRenewable(): bool
    {
        return !$this->isTest()
            && !$this->isCancelled()
            && !$this->pending_plan_id
            && $this->daysLeft() <= self::RENEWAL_WINDOW_DAYS;
    }

    public function monthsLeft(): int
    {
        $today = now()->startOfDay();
        $end = $this->end_date->copy()->startOfDay();
        $months = 0;

        while ($end->copy()->subMonthsNoOverflow($months)->gt($today)) {
            $months++;
        }

        return $months;
    }

    public function upgradeMonths(): int
    {
        return $this->isTest() ? self::TERM_MONTHS : $this->monthsLeft();
    }

    public function canUpgrade(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_ACTIVE], true)
            && $this->plans?->plan_code !== Plan::CODE_HYBRID
            && ($this->isTest() || !$this->pending_plan_id)
            && $this->upgradeMonths() > 0;
    }

    public static function proratedUpgrade(Plan $from, Plan $to, int $months, int $additionalBranches = 0): float
    {
        $difference = ((float) $to->price + $additionalBranches * (float) $to->additional_branch_price)
            - ((float) $from->price + $additionalBranches * (float) $from->additional_branch_price);

        return round($difference / self::TERM_MONTHS * $months, 2);
    }

    public function renewalSummary(): array
    {
        return [
            'days_left' => $this->daysLeft(),
            'window_days' => self::RENEWAL_WINDOW_DAYS,
            'can_renew' => $this->isRenewable(),
            'can_cancel' => $this->canCancelTest(),
            'can_upgrade' => $this->canUpgrade(),
            'can_resubscribe' => $this->isCancelled(),
            'upgrade_months' => $this->upgradeMonths(),
            'opens_at' => $this->renewalOpensAt()->toDateString(),
        ];
    }
}
