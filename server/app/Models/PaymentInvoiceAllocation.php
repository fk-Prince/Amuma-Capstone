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

    public function refundedAmount(): float
    {
        return round((float) $this->refundAllocations->sum('amount'), 2);
    }


    public function creditableAmount(): float
    {
        return round(max(0, (float) $this->amount - $this->refundedAmount()), 2);
    }
}
