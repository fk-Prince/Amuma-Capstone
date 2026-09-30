<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $primaryKey = 'transaction_id';

    public const UPDATED_AT = null;

    public const TYPE_PAYMENT = 'payment';
    public const TYPE_WITHDRAW = 'withdraw';

    public const DIRECTION_CREDIT = 'credit';
    public const DIRECTION_DEBIT = 'debit';

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'transaction_code',
        'transaction_reference_id',
        'branch_id',
        'patient_id',
        'client_id',
        'amount',
        'type',
        'direction',
        'status',
        'method',
        'party_name',
        'masked_account_number',
        'declined_reason',
        'description',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'transaction_id', 'transaction_id');
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class, 'transaction_id', 'transaction_id');
    }

    public function getInvoicesAttribute()
    {
        return $this->refunds
            ->flatMap(fn(Refund $refund) => $refund->allocations)
            ->map(fn(RefundAllocation $line) => $line->allocation?->invoice)
            ->filter()
            ->unique('invoice_id')
            ->values();
    }

    protected static function booted()
    {
        static::creating(function ($transaction) {
            if ($transaction->transaction_code) {
                return;
            }

            $last = self::lockForUpdate()
                ->whereNotNull('transaction_code')
                ->orderByDesc('transaction_id')
                ->first();

            $next = $last
                ? ((int) substr($last->transaction_code, 4)) + 1
                : 1;

            $transaction->transaction_code = 'TXN-' . str_pad(
                (string) $next,
                6,
                '0',
                STR_PAD_LEFT
            );
        });
    }
}
