<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    protected $primaryKey = 'subscription_payment_id';


    public const STATUS_PAID = 'paid';
    public const STATUS_REFUNDED = 'refunded';

    public const TYPE_SUBSCRIPTION = 'subscription';
    public const TYPE_RENEWAL = 'renewal';

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'plan_id');
    }

    protected $fillable = [
        'subscription_id',
        'plan_id',
        'xendit_invoice_id',
        'payment_reference_id',
        'masked_card_number',
        'price',
        'status',
        'type',
        'billing_interval',
        'payment_method',
    ];
}
