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
        'receipt_no',
        'reference_id',
        'branch_id',
        'patient_id',
        'client_id',
        'payor_name',
        'amount',
        'prior_balance',
        'new_balance',
        'payment_method',
        'masked_card_number',
        'issued_by',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'prior_balance' => 'decimal:2',
        'new_balance' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
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

    // A payment can settle several invoices, so its refunds are the ones raised
    // against each of its allocations.
    // The refund lines drawn from this payment's allocations. Each carries the
    // slice taken from this payment; the refund it belongs to may also have
    // drawn from others.
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
            ->map(fn($line) => $line->refund)
            ->filter()
            ->unique('refund_id')
            ->values();
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->payment_method === self::METHOD_CREDIT;
    }

    // Only the lines that settled something count as applied. A credit payment
    // also carries the negative line it was drawn from, which is a withdrawal,
    // not an application.
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

    public function getChangeDueAttribute(): float
    {
        return round(max((float) $this->amount - $this->amount_applied, 0), 2);
    }

    public function getBalanceAfterAttribute(): float
    {
        return $this->new_balance !== null
            ? round((float) $this->new_balance, 2)
            : round(max((float) $this->prior_balance - $this->amount_applied, 0), 2);
    }

    public function getMaskedAccountAttribute(): ?string
    {
        return $this->masked_card_number;
    }

    public function getAmountInWordsAttribute(): string
    {
        return MoneyWords::pesos($this->amount_applied);
    }

    protected static function booted()
    {
        static::creating(function ($payment) {
            if (!$payment->reference_id) {
                $payment->reference_id = (string) Str::uuid();
            }

            if (!$payment->receipt_no) {
                $last = self::whereNotNull('receipt_no')
                    ->orderByDesc('payment_id')
                    ->first();

                $next = $last && $last->receipt_no
                    ? ((int) substr($last->receipt_no, 4)) + 1
                    : 1;

                $payment->receipt_no = 'RCP-' . str_pad($next, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}
