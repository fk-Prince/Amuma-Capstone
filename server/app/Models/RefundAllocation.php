<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundAllocation extends Model
{
    protected $table = 'refund_allocation';

    protected $primaryKey = 'refund_allocation_id';

    public $timestamps = false;

    protected $fillable = [
        'refund_id',
        'allocation_id',
        'invoice_adjustment_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class, 'refund_id', 'refund_id');
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(
            PaymentInvoiceAllocation::class,
            'allocation_id',
            'allocation_id'
        );
    }

    public function invoiceAdjustment(): BelongsTo
    {
        return $this->belongsTo(
            InvoiceAdjustment::class,
            'invoice_adjustment_id',
            'invoice_adjustment_id'
        );
    }

    public function getInvoiceAttribute()
    {
        return $this->allocation?->invoice;
    }
}
