<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentInvoiceAllocation extends Model
{
    protected $table = 'payment_invoice_allocation';

    protected $primaryKey = 'allocation_id';

    public $timestamps = false;

    protected $fillable = [
        'payment_id',
        'invoice_id',
        'amount',
        'description',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    public function refundAllocations(): HasMany
    {
        return $this->hasMany(
            RefundAllocation::class,
            'allocation_id',
            'allocation_id'
        );
    }

    // The refunds drawing on this allocation. Their `amount` is the refund's
    // full total, so anything measuring what came off THIS allocation must sum
    // the refund allocations instead.
    public function refunds()
    {
        return $this->hasManyThrough(
            Refund::class,
            RefundAllocation::class,
            'allocation_id',
            'refund_id',
            'allocation_id',
            'refund_id'
        );
    }

    public function refundedAmount(array $statuses = [Refund::STATUS_COMPLETED]): float
    {
        return round(
            (float) $this->refundAllocations
                ->filter(fn($line) => in_array($line->refund?->status, $statuses, true))
                ->sum('amount'),
            2
        );
    }
}
