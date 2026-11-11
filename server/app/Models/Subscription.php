<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Service\AuditService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use Auditable, HasUuids;

    protected string $auditModule = 'subscriptions';

    protected $primaryKey = 'subscription_id';

    public function auditBranchId(): ?int
    {
        return app(AuditService::class)->requestBranchId()
            ?? $this->branchLinks()->value('branch_id');
    }

    public const RENEWAL_WINDOW_DAYS = 7;
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CANCELLED = 'cancelled';
    public const MODE_TEST = 'test';
    public const MODE_LIVE = 'live';
    public const TRIAL_WEEKS = 2;
    public const TERM_MONTHS = 12;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'pending_plan_starts_at' => 'date',
        'auto_renew' => 'boolean',
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
        'cancel_reason',
        'card_token_id',
        'masked_card_number',
        'card_brand',
        'auto_renew',
    ];

    protected $hidden = ['card_token_id'];

    public function auditExclude(): array
    {
        return ['updated_at', 'card_token_id'];
    }

    public const NO_CARD = [
        'card_token_id' => null,
        'masked_card_number' => null,
        'card_brand' => null,
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
            ->withPivot(['branch_subscription_id', 'uuid', 'status', 'type', 'plan_id'])
            ->withTimestamps();
    }

    private static array $planCache = [];

    private static function cachedPlan(int $planId): ?Plan
    {
        return self::$planCache[$planId] ??= Plan::find($planId);
    }

    public function planOfCode(?string $code, ?Plan $main = null): ?Plan
    {
        $main ??= $this->effectivePlan();

        if (!$code || !$main) {
            return null;
        }

        if ($main->plan_code === $code) {
            return $main;
        }

        return Plan::where('plan_code', $code)->where('type', $main->type)->first();
    }

    public function planForLink(?int $planId, ?string $type, ?Plan $main = null): ?Plan
    {
        $main ??= $this->effectivePlan();

        if (!$planId || !$main) {
            return $main;
        }

        if ($type === BranchSubscription::TYPE_INCLUDED && $main->plan_code !== Plan::CODE_HYBRID) {
            return $main;
        }

        $own = self::cachedPlan($planId);

        if (!$own) {
            return $main;
        }

        return $own->type === $main->type
            ? $own
            : ($this->planOfCode($own->plan_code, $main) ?? $own);
    }

    public function linkPlanId(?Plan $branchPlan): ?int
    {
        $main = $this->effectivePlan();

        return $branchPlan && $main && $branchPlan->plan_code !== $main->plan_code
            ? $branchPlan->plan_id
            : null;
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

        $used = $this->branches_used
            ?? $this->branchLinks()
            ->where('status', '!=', BranchSubscription::STATUS_REJECTED)
            ->where('type', BranchSubscription::TYPE_INCLUDED)
            ->count();

        return (int) $used < $this->branchLimit();
    }

    public function reopensAsTrial(): bool
    {
        return $this->status === self::STATUS_REJECTED
            && ($this->isTest() || !self::where('agency_id', $this->agency_id)
                ->where('subscription_id', '!=', $this->subscription_id)
                ->exists());
    }

    public function hasFreeResubmitSlot(): bool
    {
        return $this->status === self::STATUS_REJECTED
            ? $this->reopensAsTrial()
            : $this->hasOpenSlot();
    }

    public function additionalBranchCount(): int
    {
        return $this->branchLinks()
            ->where('status', '!=', BranchSubscription::STATUS_REJECTED)
            ->where('type', BranchSubscription::TYPE_ADDITIONAL)
            ->count();
    }

    public function hasExpired(): bool
    {
        if ($this->isTest()) {
            return false;
        }

        return $this->status === self::STATUS_EXPIRED
            || (
                $this->status === self::STATUS_ACTIVE
                && $this->end_date?->copy()->startOfDay()->lt(now()->startOfDay())
                && !$this->pendingPlanIsDue()
            );
    }

    public function billedAdditionalBranchCount(): int
    {
        return $this->branchLinks()
            ->where('status', '!=', BranchSubscription::STATUS_REJECTED)
            ->where('type', BranchSubscription::TYPE_ADDITIONAL)
            ->whereNull('active_until')
            ->count();
    }

    public function mainBranchLinkId(): ?int
    {
        return $this->branchLinks()
            ->where('type', BranchSubscription::TYPE_INCLUDED)
            ->min('branch_subscription_id');
    }

    private function billedAdditionalLinks()
    {
        return $this->branchLinks()
            ->where('status', '!=', BranchSubscription::STATUS_REJECTED)
            ->where('type', BranchSubscription::TYPE_ADDITIONAL)
            ->whereNull('active_until')
            ->get(['branch_subscription_id', 'plan_id', 'type']);
    }

    public function renewalAmount(Plan $plan): float
    {
        return round((float) $plan->price + $this->additionalYearly($plan), 2);
    }

    public function additionalYearly(?Plan $main = null): float
    {
        $current = $this->effectivePlan();
        $main ??= $current;

        return round((float) $this->billedAdditionalLinks()->sum(
            fn(BranchSubscription $link) => (float) $this->planForLink(
                $link->plan_id ?? $current?->plan_id,
                $link->type,
                $main
            )?->additional_branch_price
        ), 2);
    }

    public function pinAdditionalBranches(): void
    {
        $current = $this->effectivePlan();

        if (!$current) {
            return;
        }

        $this->branchLinks()
            ->where('type', BranchSubscription::TYPE_ADDITIONAL)
            ->whereNull('plan_id')
            ->update(['plan_id' => $current->plan_id]);
    }

    public function canAddAdditionalBranch(?string $planCode = null): bool
    {
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_ACTIVE], true)) {
            return false;
        }

        if ($this->isTest() || $this->upgradeMonths() <= 0) {
            return false;
        }

        if (!$this->payments()->where('status', SubscriptionPayment::STATUS_PAID)->exists()) {
            return false;
        }

        if (!$this->hasOpenSlot()) {
            return true;
        }

        $mainCode = $this->effectivePlan()?->plan_code;

        return $planCode
            ? !Plan::covers($mainCode, $planCode)
            : $mainCode !== Plan::CODE_HYBRID;
    }

    public function additionalBranchQuote(?Plan $branchPlan = null): array
    {
        $months = $this->upgradeMonths();
        $unit = (float) (($branchPlan ?? $this->plans)?->additional_branch_price ?? 0);

        return [
            'additional_branch_price' => $unit,
            'months' => $months,
            'amount' => self::proratedAdditionalBranch($unit, $months),
        ];
    }

    public function additionalPlanOptions(): array
    {
        $main = $this->effectivePlan();

        if (!$main) {
            return [];
        }

        $openSlot = $this->hasOpenSlot();

        return Plan::where('type', $main->type)
            ->orderBy('plan_code')
            ->get()
            ->map(fn(Plan $plan) => [
                'plan_code' => $plan->plan_code,
                'name' => $plan->name,
                'available' => !$openSlot || !Plan::covers($main->plan_code, $plan->plan_code),
                ...$this->additionalBranchQuote($plan),
            ])
            ->values()
            ->all();
    }

    public function includedPlanCodes(): array
    {
        $mainCode = $this->effectivePlan()?->plan_code;

        return array_values(array_filter(Plan::CODES, fn($code) => Plan::covers($mainCode, $code)));
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
            'additional_branches' => $this->billedAdditionalBranchCount(),
            'additional_yearly' => $this->additionalYearly(),
            'deactivated_additional_branches' => $this->additionalBranchCount() - $this->billedAdditionalBranchCount(),
            'included_used' => $this->branchLinks()
                ->where('status', '!=', BranchSubscription::STATUS_REJECTED)
                ->where('type', BranchSubscription::TYPE_INCLUDED)
                ->count(),
            'included_deactivated' => $this->branchLinks()
                ->where('status', '!=', BranchSubscription::STATUS_REJECTED)
                ->where('type', BranchSubscription::TYPE_INCLUDED)
                ->whereNotNull('active_until')
                ->count(),
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

    public function isPaid(): bool
    {
        return (bool) ($this->has_paid_payment
            ?? $this->payments()->where('status', SubscriptionPayment::STATUS_PAID)->exists());
    }

    public function awaitingTrialPayment(): bool
    {
        return $this->isTest()
            && in_array($this->status, [self::STATUS_PENDING, self::STATUS_ACTIVE, self::STATUS_EXPIRED], true)
            && !$this->isPaid();
    }

    public function trialHasEnded(): bool
    {
        return $this->isTest()
            && ($this->status === self::STATUS_EXPIRED || $this->pendingPlanIsDue());
    }

    public function isRejectedFreeTrial(): bool
    {
        return $this->status === self::STATUS_REJECTED
            && $this->isTest()
            && !$this->payments()->exists();
    }

    public function canPayTrial(): bool
    {
        return $this->awaitingTrialPayment();
    }

    public function canStartPlanNow(): bool
    {
        return $this->canPayTrial() && $this->status !== self::STATUS_PENDING;
    }

    public function isLockedForPayment(): bool
    {
        return $this->awaitingTrialPayment() && $this->trialHasEnded();
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
        $testEnds = $startsAt->copy()->addWeeks(self::TRIAL_WEEKS);

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
            && !$this->awaitingTrialPayment()
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
            'awaiting_payment' => $this->awaitingTrialPayment(),
            'can_pay' => $this->canPayTrial(),
            'can_start_now' => $this->canStartPlanNow(),
            'trial_ended' => $this->trialHasEnded(),
            'upgrade_months' => $this->upgradeMonths(),
            'opens_at' => $this->renewalOpensAt()->toDateString(),
            'card' => $this->savedCard(),
        ];
    }

    public function cardLast4(): ?string
    {
        return $this->masked_card_number ? substr($this->masked_card_number, -4) : null;
    }

    public function nextCardCharge(): ?array
    {
        if ($this->awaitingTrialPayment()) {
            return [$this->pending_plan_starts_at, $this->plans];
        }

        if (!$this->auto_renew) {
            return null;
        }

        return $this->pending_plan_id && $this->pending_plan_starts_at
            ? [self::termEnd($this->pending_plan_starts_at), $this->pendingPlan]
            : [$this->end_date, $this->plans];
    }

    public function savedCard(): ?array
    {
        if (
            !$this->card_token_id
            || !in_array($this->status, [self::STATUS_PENDING, self::STATUS_ACTIVE], true)
            || $this->isLockedForPayment()
        ) {
            return null;
        }

        [$chargesOn, $plan] = $this->nextCardCharge() ?? [null, null];

        return [
            'brand' => $this->card_brand,
            'last4' => $this->cardLast4(),
            'auto_renew' => (bool) $this->auto_renew,
            'is_trial' => $this->awaitingTrialPayment(),
            'charges_on' => $chargesOn?->toDateString(),
            'amount' => $plan ? round($this->renewalAmount($plan), 2) : null,
        ];
    }
}
