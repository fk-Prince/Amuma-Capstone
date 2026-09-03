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

    protected $fillable = [
        'subscription_id',
        'branch_id',
        'status',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
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
