<?php

namespace App\Models;

use App\Enums\BillingIntervalEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasUuids;
    protected $primaryKey = 'subscription_id';

    public const BRANCH_LIMIT = 5;
    public const RENEWAL_WINDOW_DAYS = 7;
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PENDING = 'pending';

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
        'pending_billing_interval',
        'agency_id',
        'status',
        'billing_interval',
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
            'billing_interval' => $this->pending_billing_interval,
            'starts_at' => $this->pending_plan_starts_at?->toDateString(),
            'is_due' => $this->pendingPlanIsDue(),
        ];
    }

    public function pendingPlanChanges(?Carbon $startsAt = null): array
    {
        $startsAt = $startsAt ?? $this->pending_plan_starts_at->copy();
        $interval = BillingIntervalEnum::from($this->pending_billing_interval ?? $this->billing_interval);

        return [
            'plan_id' => $this->pending_plan_id,
            'billing_interval' => $interval->value,
            'start_date' => $startsAt,
            'end_date' => $interval->addTo($startsAt),
            'pending_plan_id' => null,
            'pending_plan_starts_at' => null,
            'pending_billing_interval' => null,
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
        if (in_array($this->status, [self::STATUS_REJECTED, self::STATUS_EXPIRED], true)) {
            return false;
        }

        $hasPaid = $this->has_paid_payment
            ?? $this->payments()->where('status', SubscriptionPayment::STATUS_PAID)->exists();

        $used = $this->branches_used
            ?? $this->branchLinks()->where('status', '!=', BranchSubscription::STATUS_REJECTED)->count();

        return $hasPaid && (int) $used < self::BRANCH_LIMIT;
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

    public function isRenewable(): bool
    {
        return $this->daysLeft() <= self::RENEWAL_WINDOW_DAYS;
    }

    public function renewalSummary(): array
    {
        return [
            'days_left' => $this->daysLeft(),
            'window_days' => self::RENEWAL_WINDOW_DAYS,
            'can_renew' => $this->isRenewable(),
            'opens_at' => $this->renewalOpensAt()->toDateString(),
        ];
    }
}
