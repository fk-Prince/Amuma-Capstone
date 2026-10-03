<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BranchSubscription extends Pivot
{
    use HasUuids;

    protected $table = 'branch_subscription';
    protected $primaryKey = 'branch_subscription_id';
    public $incrementing = true;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const TYPE_INCLUDED = 'included';
    public const TYPE_ADDITIONAL = 'additional';

    protected $fillable = [
        'subscription_id',
        'branch_id',
        'status',
        'type',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
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
