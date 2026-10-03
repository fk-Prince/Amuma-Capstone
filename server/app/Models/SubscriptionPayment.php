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
    public const TYPE_UPGRADE = 'upgrade';
    public const TYPE_ADDITIONAL_BRANCH = 'additional_branch';

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'plan_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id', 'subscription_id');
    }

    public function historyRow(): array
    {
        return [
            'subscription_payment_id' => $this->subscription_payment_id,
            'plan_name' => $this->plan?->name,
            'plan_type' => $this->plan?->type,
            'xendit_invoice_id' => $this->xendit_invoice_id,
            'payment_reference_id' => $this->payment_reference_id,
            'masked_card_number' => $this->masked_card_number,
            'price' => (float) $this->price,
            'status' => $this->status,
            'type' => $this->type,
            'branch_name' => $this->branch_id ? $this->branch?->name : null,
            'branch_uuid' => $this->branch_id ? $this->branch?->uuid : null,
            'payment_method' => $this->payment_method,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    protected $fillable = [
        'subscription_id',
        'plan_id',
        'branch_id',
        'xendit_invoice_id',
        'payment_reference_id',
        'masked_card_number',
        'price',
        'status',
        'type',
        'payment_method',
    ];
}
