<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BranchSubscription extends Pivot
{
    use Auditable, HasUuids;

    protected string $auditModule = 'subscriptions';

    protected $table = 'branch_subscription';
    protected $primaryKey = 'branch_subscription_id';
    public $incrementing = true;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const TYPE_INCLUDED = 'included';
    public const TYPE_ADDITIONAL = 'additional';

    public const STATE_ACTIVE = 'active';
    public const STATE_ENDING = 'ending';
    public const STATE_INACTIVE = 'inactive';

    protected $fillable = [
        'subscription_id',
        'branch_id',
        'plan_id',
        'status',
        'type',
        'active_until',
    ];

    protected $casts = [
        'active_until' => 'date',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function activityState(): string
    {
        if (!$this->active_until) {
            return self::STATE_ACTIVE;
        }

        return $this->active_until->copy()->startOfDay()->lt(today())
            ? self::STATE_INACTIVE
            : self::STATE_ENDING;
    }

    public function reactivationAmount(): float
    {
        $subscription = $this->subscription;

        if (
            !$this->active_until
            || $this->type === self::TYPE_INCLUDED
            || !$subscription
            || $this->active_until->gte($subscription->end_date)
        ) {
            return 0.0;
        }

        return (float) $subscription->additionalBranchQuote($this->effectivePlan())['amount'];
    }

    public static function hasPlanCodeSql(string $codes, string $branchColumn = 'branches.branch_id'): string
    {
        return "EXISTS (
            SELECT 1 FROM branch_subscription bs
            JOIN subscriptions s ON s.subscription_id = bs.subscription_id
            JOIN plans sp ON sp.plan_id = s.plan_id
            LEFT JOIN plans lp ON lp.plan_id = bs.plan_id
            WHERE bs.branch_id = {$branchColumn}
              AND (CASE
                    WHEN lp.plan_id IS NULL OR (bs.type = '" . self::TYPE_INCLUDED . "' AND sp.plan_code <> '" . Plan::CODE_HYBRID . "')
                    THEN sp.plan_code
                    ELSE lp.plan_code
                  END) IN ({$codes})
        )";
    }

    public function effectivePlan(): ?Plan
    {
        return $this->subscription?->planForLink($this->plan_id, $this->type);
    }

    public function isMain(): bool
    {
        $subscription = $this->subscription;

        return $subscription
            && (int) $subscription->mainBranchLinkId() === (int) $this->branch_subscription_id;
    }

    public function canChangePlan(): bool
    {
        return $this->type === self::TYPE_INCLUDED
            && $this->status !== self::STATUS_REJECTED
            && !$this->isMain()
            && $this->subscription?->effectivePlan()?->plan_code === Plan::CODE_HYBRID
            && $this->effectivePlan()?->plan_code !== Plan::CODE_HYBRID;
    }

    public function upgradeOptions(): array
    {
        $subscription = $this->subscription;

        if (
            $this->type !== self::TYPE_ADDITIONAL
            || $this->status !== self::STATUS_APPROVED
            || $this->active_until
            || !$subscription
            || $subscription->status !== Subscription::STATUS_ACTIVE
            || $subscription->upgradeMonths() <= 0
        ) {
            return [];
        }

        $current = $this->effectivePlan();

        if (!$current) {
            return [];
        }

        $months = $subscription->upgradeMonths();
        $currentPrice = (float) $current->additional_branch_price;

        return Plan::where('type', $current->type)
            ->orderBy('plan_code')
            ->get()
            ->filter(fn(Plan $plan) => $plan->plan_code !== $current->plan_code
                && Plan::covers($plan->plan_code, $current->plan_code)
                && (float) $plan->additional_branch_price > $currentPrice)
            ->map(fn(Plan $plan) => [
                'plan_code' => $plan->plan_code,
                'name' => $plan->name,
                'additional_branch_price' => (float) $plan->additional_branch_price,
                'current_price' => $currentPrice,
                'months' => $months,
                'amount' => Subscription::proratedAdditionalBranch(
                    (float) $plan->additional_branch_price - $currentPrice,
                    $months
                ),
            ])
            ->values()
            ->all();
    }

    public function activationSummary(): array
    {
        $subscription = $this->subscription;
        $plan = $this->effectivePlan();

        return [
            'uuid' => $this->branch?->uuid,
            'status' => $this->status,
            'slot_type' => $this->type,
            'active_until' => $this->active_until?->toDateString(),
            'activity_state' => $this->activityState(),
            'is_main' => $this->isMain(),
            'plan' => $plan ? [
                'plan_code' => $plan->plan_code,
                'name' => $plan->name,
            ] : null,
            'can_change_plan' => $this->canChangePlan(),
            'upgrade_options' => $this->upgradeOptions(),
            'reactivation_amount' => $this->reactivationAmount(),
            'additional_branch_price' => (float) $plan?->additional_branch_price,
            'renews_on' => $subscription?->end_date?->toDateString(),
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'plan_id');
    }

    public function isDeactivated(): bool
    {
        return $this->active_until !== null;
    }

    public function verificationLogs()
    {
        return $this->hasMany(VerificationLog::class, 'branch_subscription_id', 'branch_subscription_id');
    }

    public function latestRejection()
    {
        return $this->hasOne(VerificationLog::class, 'branch_subscription_id', 'branch_subscription_id')
            ->ofMany(
                ['verification_log_id' => 'max'],
                fn($query) => $query->where('action', VerificationLog::ACTION_REJECTED)
            );
    }

    public function getRejectionReasonAttribute(): ?string
    {
        return $this->latestRejection?->reason;
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id', 'subscription_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }
}
