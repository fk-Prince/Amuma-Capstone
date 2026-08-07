<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $primaryKey = 'payment_id';

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'amount',
        'reference_id',
        'payment_method',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    protected static function booted()
    {
        static::creating(function ($payment) {

            if (!$payment->reference_id) {
                $lastPayment = self::lockForUpdate()
                    ->whereNotNull('reference_id')
                    ->orderByDesc('payment_id')
                    ->first();
                $nextNumber = $lastPayment
                    ? ((int) substr($lastPayment->reference_id, 8)) + 1
                    : 1;
                $payment->reference_id = 'PAYMENT-' . str_pad(
                    $nextNumber,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }
}
