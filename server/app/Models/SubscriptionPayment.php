<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    protected $primaryKey = 'subscription_payment_id';


    public const STATUS_PAID = 'paid';
    public const STATUS_REFUNDED = 'refunded';
    protected $fillable = [
        'subscription_id',
        'xendit_invoice_id',
        'payment_reference_id',
        'masked_card_number',
        'price',
        'status'
    ];
}
