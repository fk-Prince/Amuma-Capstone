<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $primaryKey = 'payment_id';

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'receipt_id',
        'amount',
        'reference_id',
        'payment_method',
        'masked_card_number',
        'prior_balance',
        'new_balance',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'prior_balance' => 'decimal:2',
        'new_balance' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(PaymentReceipt::class, 'receipt_id', 'receipt_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'payment_id', 'payment_id');
    }

    protected static function booted()
    {
        static::creating(function ($payment) {
            if (!$payment->reference_id) {
                $payment->reference_id = (string) Str::uuid();
            }
        });
    }
}
