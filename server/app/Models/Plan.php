<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $primaryKey = 'plan_id';
    public $timestamps = false;

    protected $fillable = [
        'plan_code',
        'monthly_price',
        'yearly_price',
        'description',
        'name'
    ];

    protected $appends = ['branch_limit'];

    public function getBranchLimitAttribute(): int
    {
        return Subscription::BRANCH_LIMIT;
    }


    public function subscription()
    {
        return $this->hasMany(Subscription::class, 'plan_id', 'plan_id');
    }
}
