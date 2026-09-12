<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Refund extends Model
{
    protected $table = 'refunds';

    protected $primaryKey = 'refund_id';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'amount',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'transaction_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(RefundAllocation::class, 'refund_id', 'refund_id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where(
            fn($q) => $q->whereNull('transaction_id')
                ->orWhereHas(
                    'transaction',
                    fn($t) => $t->where('status', Transaction::STATUS_REJECTED)
                )
        );
    }

    public function scopeForPatient(Builder $query, mixed $patientId): Builder
    {
        return $query->whereHas(
            'allocations.allocation.invoice',
            fn($invoice) => $invoice->whereIn(
                'invoice_id',
                Invoice::patientInvoiceIds($patientId)
            )
        );
    }

    public function getIsAvailableAttribute(): bool
    {
        return !$this->transaction_id
            || $this->transaction?->status === Transaction::STATUS_REJECTED;
    }

    public function getInvoicesAttribute()
    {
        return $this->allocations
            ->map(fn(RefundAllocation $line) => $line->allocation?->invoice)
            ->filter()
            ->unique('invoice_id')
            ->values();
    }
}
