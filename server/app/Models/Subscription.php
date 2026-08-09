<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasUuids;
    //
    protected $primaryKey = 'subscription_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    public function uniqueIds()
    {
        return ['uuid'];
    }
    protected $fillable = [
        'plan_id',
        'branch_id',
        'billing_interval',
        'status',
        'start_date',
        'end_date',
    ];

    public function plans()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'plan_id',);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }


    public function subscription_payments()
    {
        return $this->hasMany(SubscriptionPayment::class, 'subscription_id', 'subscription_id');
    }
}
