<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $primaryKey = 'plan_id';
    public $timestamps = false;

    protected $fillable = [
        'plan_code',
        'monthly_price_id',
        'yearly_price_id',
        'description',
        'name'
    ];

    public function monthlyPrice()
    {
        return $this->belongsTo(Price::class, 'monthly_price_id', 'price_id');
    }

    public function yearlyPrice()
    {
        return $this->belongsTo(Price::class, 'yearly_price_id', 'price_id');
    }

    public function subscription()
    {
        return $this->hasMany(Subscription::class, 'plan_id', 'plan_id');
    }
}
