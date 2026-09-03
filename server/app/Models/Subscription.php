<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasUuids;
    protected $primaryKey = 'subscription_id';

    public const BRANCH_LIMIT = 5;
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
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
        'agency_id',
        'billing_interval',
        'status',
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

    public function effectivePlan(): ?Plan
    {
        return $this->pendingPlanIsDue() ? $this->pendingPlan : $this->plans;
    }

    public function pendingPlanIsDue(): bool
    {
        return $this->pending_plan_id
            && $this->pending_plan_starts_at
            && !now()->startOfDay()->lt($this->pending_plan_starts_at);
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


    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class, 'subscription_id', 'subscription_id');
    }
}
