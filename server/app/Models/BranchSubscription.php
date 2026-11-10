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

        return (float) $subscription->additionalBranchQuote()['amount'];
    }

    public function activationSummary(): array
    {
        $subscription = $this->subscription;

        return [
            'uuid' => $this->branch?->uuid,
            'status' => $this->status,
            'slot_type' => $this->type,
            'active_until' => $this->active_until?->toDateString(),
            'activity_state' => $this->activityState(),
            'is_main' => $subscription
                && (int) $subscription->mainBranchLinkId() === (int) $this->branch_subscription_id,
            'reactivation_amount' => $this->reactivationAmount(),
            'additional_branch_price' => (float) $subscription?->plans?->additional_branch_price,
            'renews_on' => $subscription?->end_date?->toDateString(),
        ];
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
