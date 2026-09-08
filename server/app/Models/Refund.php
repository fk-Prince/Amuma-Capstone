<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $primaryKey = 'refund_id';


    public const STATUS_REQUESTED = 'requested';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DECLINED = 'declined';

    public const SETTLED_STATUSES = [
        self::STATUS_COMPLETED,
    ];

    protected $fillable = [
        'amount',
        'refund_method',
        'refund_code',
        'status',
        'declined_reason',
        'masked_card_number',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function allocations()
    {
        return $this->hasMany(
            RefundAllocation::class,
            'refund_id',
            'refund_id'
        );
    }

    public function payments()
    {
        return $this->hasManyThrough(
            PaymentInvoiceAllocation::class,
            RefundAllocation::class,
            'refund_id',
            'allocation_id',
            'refund_id',
            'allocation_id'
        );
    }

    public function getInvoicesAttribute()
    {
        return $this->allocations
            ->map(fn($allocation) => $allocation->allocation?->invoice)
            ->filter()
            ->unique('invoice_id')
            ->values();
    }

    public function getInvoiceAttribute()
    {
        return $this->invoices->first();
    }

    protected static function booted()
    {
        static::creating(function ($refund) {
            if (!$refund->status) {
                $refund->status = self::STATUS_REQUESTED;
            }

            if (!$refund->refund_code) {
                $lastRefund = self::lockForUpdate()
                    ->whereNotNull('refund_code')
                    ->orderByDesc('refund_id')
                    ->first();

                $nextNumber = $lastRefund
                    ? ((int) substr($lastRefund->refund_code, 7)) + 1
                    : 1;

                $refund->refund_code = 'REFUND-' . str_pad(
                    $nextNumber,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }
}
