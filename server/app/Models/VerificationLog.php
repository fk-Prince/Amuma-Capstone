<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationLog extends Model
{
    public const ACTION_APPROVED = 'approved';
    public const ACTION_REJECTED = 'rejected';

    public const SCOPE_BRANCH = 'branch';
    public const SCOPE_AGENCY = 'agency';
    public const SCOPE_BOTH = 'both';

    protected $primaryKey = 'verification_log_id';

    protected $fillable = [
        'branch_subscription_id',
        'action',
        'scope',
        'reason',
        'action_by',
    ];

    public function branchSubscription()
    {
        return $this->belongsTo(BranchSubscription::class, 'branch_subscription_id', 'branch_subscription_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'action_by', 'user_id');
    }
}
