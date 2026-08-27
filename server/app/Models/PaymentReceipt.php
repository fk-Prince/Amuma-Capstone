<?php

namespace App\Models;

use App\Utils\MoneyWords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentReceipt extends Model
{
    protected $primaryKey = 'receipt_id';

    public $timestamps = false;

    protected $fillable = [
        'receipt_no',
        'branch_id',
        'patient_id',
        'client_id',
        'payor_name',
        'amount_tendered',
        'balance_before',
        'issued_by',
        'created_at',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'amount_tendered' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'created_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(
            Payment::class,
            'receipt_id',
            'receipt_id'
        )->orderBy('payment_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(
            Branch::class,
            'branch_id',
            'branch_id'
        );
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(
            Patient::class,
            'patient_id',
            'patient_id'
        );
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(
            Client::class,
            'client_id',
            'client_id'
        );
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'issued_by',
            'user_id'
        );
    }


    protected static function booted()
    {
        static::creating(function ($receipt) {
            if (!$receipt->receipt_no) {
                $lastReceipt = self::whereNotNull('receipt_no')
                    ->orderByDesc('receipt_id')
                    ->lockForUpdate()
                    ->first();

                $nextNumber = 1;

                if ($lastReceipt && $lastReceipt->receipt_no) {
                    $lastNumber = (int) substr($lastReceipt->receipt_no, 4);
                    $nextNumber = $lastNumber + 1;
                }

                $receipt->receipt_no = 'RCP-' . str_pad(
                    $nextNumber,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    public function getIsVoidedAttribute(): bool
    {
        return $this->voided_at !== null;
    }

    public function getAmountAppliedAttribute(): float
    {
        return round((float) $this->payments->sum('amount'), 2);
    }

    public function getChangeDueAttribute(): float
    {
        return round(
            max((float) $this->amount_tendered - $this->amount_applied, 0),
            2
        );
    }

    public function getBalanceAfterAttribute(): float
    {
        return round(
            max((float) $this->balance_before - $this->amount_applied, 0),
            2
        );
    }

    public function getPaymentMethodAttribute(): ?string
    {
        return $this->payments->first()?->payment_method;
    }

    public function getMaskedAccountAttribute(): ?string
    {
        return $this->payments->first()?->masked_card_number;
    }

    public function getAmountInWordsAttribute(): string
    {
        return MoneyWords::pesos($this->amount_applied);
    }
}
