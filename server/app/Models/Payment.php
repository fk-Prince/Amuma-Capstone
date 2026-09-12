<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Utils\MoneyWords;
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $primaryKey = 'payment_id';

    public const METHOD_CREDIT = 'CREDIT';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'payor_name',
        'prior_balance',
        'new_balance',
        'cash_tendered',
        'issued_by',
        'created_at',
    ];

    protected $casts = [
        'prior_balance' => 'decimal:2',
        'new_balance' => 'decimal:2',
        'cash_tendered' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'transaction_id');
    }

    // The money and the parties live on the ledger entry; the payment record
    // only says how it was taken.
    public function getAmountAttribute(): float
    {
        return round((float) ($this->transaction?->amount ?? 0), 2);
    }

    // A receipt is identified by the ledger entry it belongs to; there is no
    // separate RCP series any more.
    public function getPaymentCodeAttribute()
    {
        return $this->transaction?->transaction_code;
    }

    public function getReferenceIdAttribute()
    {
        return $this->transaction?->transaction_reference_id;
    }

    // How the money was taken lives on the ledger entry alongside the amount,
    // so these read through rather than duplicating the columns.
    public function getPaymentMethodAttribute()
    {
        return $this->transaction?->method;
    }

    public function getMaskedAccountDetailAttribute()
    {
        return $this->transaction?->masked_account_number;
    }

    public function getClientIdAttribute()
    {
        return $this->transaction?->client_id;
    }

    public function getClientAttribute()
    {
        return $this->transaction?->client;
    }

    public function getBranchIdAttribute()
    {
        return $this->transaction?->branch_id;
    }

    public function getPatientIdAttribute()
    {
        return $this->transaction?->patient_id;
    }

    public function getBranchAttribute()
    {
        return $this->transaction?->branch;
    }

    public function getPatientAttribute()
    {
        return $this->transaction?->patient;
    }


    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by', 'user_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(
            PaymentInvoiceAllocation::class,
            'payment_id',
            'payment_id'
        );
    }

    public function invoices()
    {
        return $this->belongsToMany(
            Invoice::class,
            'payment_invoice_allocation',
            'payment_id',
            'invoice_id',
            'payment_id',
            'invoice_id'
        )->withPivot(['allocation_id', 'amount', 'description']);
    }

    public function refundAllocations()
    {
        return $this->hasManyThrough(
            RefundAllocation::class,
            PaymentInvoiceAllocation::class,
            'payment_id',
            'allocation_id',
            'payment_id',
            'allocation_id'
        );
    }

    public function getRefundsAttribute()
    {
        return $this->refundAllocations
            ->map(fn(RefundAllocation $line) => $line->refund)
            ->filter()
            ->unique('refund_id')
            ->values();
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->payment_method === self::METHOD_CREDIT;
    }

    public function getAllocatedAmountAttribute(): float
    {
        return round(
            (float) $this->allocations
                ->filter(fn($allocation) => (float) $allocation->amount > 0)
                ->sum('amount'),
            2
        );
    }

    public function getUnallocatedAmountAttribute(): float
    {
        return round((float) $this->amount - $this->allocated_amount, 2);
    }

    public function getAmountAppliedAttribute(): float
    {
        return $this->allocated_amount;
    }

    // The ledger amount is what was actually kept, so change only exists
    // against what was physically handed over — recorded separately because
    // it never belonged to the branch even for the moment it sat in the till.
    public function getAmountTenderedAttribute(): float
    {
        return round((float) ($this->cash_tendered ?? $this->amount), 2);
    }

    public function getChangeDueAttribute(): float
    {
        return round(max($this->amount_tendered - $this->amount_applied, 0), 2);
    }

    public function getBalanceAfterAttribute(): float
    {
        return $this->new_balance !== null
            ? round((float) $this->new_balance, 2)
            : round(max((float) $this->prior_balance - $this->amount_applied, 0), 2);
    }

    public function getMaskedAccountAttribute(): ?string
    {
        return $this->masked_account_detail;
    }

    public function getAmountInWordsAttribute(): string
    {
        return MoneyWords::pesos($this->amount_applied);
    }

}
