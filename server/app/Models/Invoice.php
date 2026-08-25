<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';
    public const STATUS_VOID = 'void';

    protected $primaryKey = 'invoice_id';

    public $timestamps = false;

    protected $fillable = [
        'total',
        'is_collected',
        'branch_id',
        'invoice_code',
        'status',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'is_collected' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(
            Branch::class,
            'branch_id',
            'branch_id'
        );
    }

    public function invoiceServices(): HasMany
    {
        return $this->hasMany(
            InvoiceServices::class,
            'invoice_id',
            'invoice_id'
        );
    }

    public function payments(): HasMany
    {
        return $this->hasMany(
            Payment::class,
            'invoice_id',
            'invoice_id'
        );
    }

    public function invoiceFacility(): HasMany
    {
        return $this->hasMany(
            InvoiceFacility::class,
            'invoice_id',
            'invoice_id'
        );
    }

    public function invoiceAdjustments(): HasMany
    {
        return $this->hasMany(
            InvoiceAdjustment::class,
            'invoice_id',
            'invoice_id'
        );
    }

    protected static function booted()
    {
        static::creating(function ($invoice) {
            if (!$invoice->invoice_code) {
                $lastInvoice = self::whereNotNull('invoice_code')
                    ->orderByDesc('invoice_id')
                    ->first();

                $nextNumber = 1;

                if ($lastInvoice && $lastInvoice->invoice_code) {
                    $lastNumber = (int) substr(
                        $lastInvoice->invoice_code,
                        4
                    );

                    $nextNumber = $lastNumber + 1;
                }

                $invoice->invoice_code = 'INV-' . str_pad(
                    $nextNumber,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    public function getAmountPaidAttribute(): float
    {
        return round(
            (float) $this->payments->sum('amount'),
            2
        );
    }

    public function getRefundedAmountAttribute(): float
    {
        return round(
            (float) $this->payments
                ->flatMap(
                    fn(Payment $payment) => $payment->refunds
                )
                ->whereIn('status', [
                    Refund::STATUS_COMPLETED,
                    Refund::STATUS_PROCESSING,
                ])
                ->sum('amount'),
            2
        );
    }

    public function getRefundedCompletedAmountAttribute(): float
    {
        return round(
            (float) $this->payments
                ->flatMap(
                    fn(Payment $payment) => $payment->refunds
                )
                ->where(
                    'status',
                    Refund::STATUS_COMPLETED
                )
                ->sum('amount'),
            2
        );
    }

    public function getRefundedProcessingAmountAttribute(): float
    {
        return round(
            (float) $this->payments
                ->flatMap(
                    fn(Payment $payment) => $payment->refunds
                )
                ->where(
                    'status',
                    Refund::STATUS_PROCESSING
                )
                ->sum('amount'),
            2
        );
    }

    public function getNetPaidAmountAttribute(): float
    {
        return round(
            max(
                $this->amount_paid - $this->refunded_amount,
                0
            ),
            2
        );
    }

    public function getLatestAdjustmentAttribute(): float
    {
        $adjustment = $this->invoiceAdjustments
            ->sortByDesc('created_at')
            ->first();

        return round(
            (float) ($adjustment?->amount ?? 0),
            2
        );
    }

    public function getAdjustedTotalAttribute(): float
    {
        $adjustment = $this->latest_adjustment;

        if ($adjustment <= 0) {
            return round(
                (float) $this->total,
                2
            );
        }

        return round(
            $adjustment,
            2
        );
    }

    public function getBalanceDueAttribute(): float
    {
        return round(
            max(
                $this->adjusted_total - $this->net_paid_amount,
                0
            ),
            2
        );
    }

    public function getRefundStatusAttribute(): string
    {
        if ($this->refunded_amount <= 0) {
            return 'none';
        }

        return $this->net_paid_amount <= 0
            ? 'full refunded'
            : 'partially refunded';
    }
}

// namespace App\Models;

// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Support\Facades\Log;

// class Invoice extends Model
// {
//     public const STATUS_PENDING = 'pending';
//     public const STATUS_PARTIAL = 'partial';
//     public const STATUS_PAID = 'paid';
//     public const STATUS_VOID = 'void';

//     protected $primaryKey = 'invoice_id';

//     public $timestamps = false;

//     protected $fillable = [
//         'total',
//         'is_collected',
//         'branch_id',
//         'invoice_code',
//         'status',
//     ];

//     protected $casts = [
//         'total' => 'decimal:2',
//         'is_collected' => 'boolean',
//         'created_at' => 'datetime',
//     ];

//     public function branch(): BelongsTo
//     {
//         return $this->belongsTo(
//             Branch::class,
//             'branch_id',
//             'branch_id'
//         );
//     }

//     public function invoiceServices(): HasMany
//     {
//         return $this->hasMany(
//             InvoiceServices::class,
//             'invoice_id',
//             'invoice_id'
//         );
//     }

//     public function payments(): HasMany
//     {
//         return $this->hasMany(
//             Payment::class,
//             'invoice_id',
//             'invoice_id'
//         );
//     }

//     public function invoiceFacility(): HasMany
//     {
//         return $this->hasMany(
//             InvoiceFacility::class,
//             'invoice_id',
//             'invoice_id'
//         );
//     }

//     public function invoiceAdjustments(): HasMany
//     {
//         return $this->hasMany(
//             InvoiceAdjustment::class,
//             'invoice_id',
//             'invoice_id'
//         );
//     }

//     protected static function booted()
//     {
//         static::creating(function ($invoice) {
//             if (!$invoice->invoice_code) {
//                 $lastInvoice = self::whereNotNull('invoice_code')
//                     ->orderByDesc('invoice_id')
//                     ->first();

//                 $nextNumber = 1;

//                 if ($lastInvoice && $lastInvoice->invoice_code) {
//                     $lastNumber = (int) substr(
//                         $lastInvoice->invoice_code,
//                         4
//                     );

//                     $nextNumber = $lastNumber + 1;
//                 }

//                 $invoice->invoice_code = 'INV-' . str_pad(
//                     $nextNumber,
//                     6,
//                     '0',
//                     STR_PAD_LEFT
//                 );
//             }
//         });
//     }

//     public function getAmountPaidAttribute(): float
//     {
//         return (float) $this->payments->sum('amount');
//     }

//     public function getRefundedAmountAttribute(): float
//     {
//         return (float) $this->payments
//             ->flatMap(fn(Payment $payment) => $payment->refunds)
//             ->whereIn('status', [
//                 Refund::STATUS_COMPLETED,
//                 Refund::STATUS_PROCESSING,
//             ])
//             ->sum('amount');
//     }

//     public function getRefundedCompletedAmountAttribute(): float
//     {
//         return (float) $this->payments
//             ->flatMap(fn(Payment $payment) => $payment->refunds)
//             ->where('status', Refund::STATUS_COMPLETED)
//             ->sum('amount');
//     }

//     public function getRefundedProcessingAmountAttribute(): float
//     {
//         return (float) $this->payments
//             ->flatMap(fn(Payment $payment) => $payment->refunds)
//             ->where('status',  Refund::STATUS_PROCESSING)
//             ->sum('amount');
//     }

//     public function getNetPaidAmountAttribute(): float
//     {
//         return max($this->amount_paid - $this->refunded_amount,  0);
//     }

//     public function getAdjustedTotalAttribute(): float
//     {
//         $adjustments = (float) $this->invoiceAdjustments->sum('amount');

//         return max((float) $this->total - $adjustments,   0);
//     }

//     public function getBalanceDueAttribute(): float
//     {
//         return max($this->adjusted_total - $this->net_paid_amount, 0);
//     }

//     public function getRefundStatusAttribute(): string
//     {
//         if ($this->refunded_amount <= 0) {
//             return 'none';
//         }

//         return $this->net_paid_amount <= 0
//             ? 'full refunded'
//             : 'partially refunded';
//     }
// }
