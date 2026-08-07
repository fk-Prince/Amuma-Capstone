<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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
        'invoice_code'
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'is_collected' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function invoiceServices(): HasMany
    {
        return $this->hasMany(InvoiceServices::class, 'invoice_id', 'invoice_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'invoice_id', 'invoice_id');
    }


    public function invoiceFacility()
    {
        return $this->hasMany(InvoiceFacility::class, 'invoice_id', 'invoice_id');
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
                    $lastNumber = (int) substr($lastInvoice->invoice_code, 4);
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
        return (float) $this->payments()->sum('amount');
    }

    public function getBalanceDueAttribute(): float
    {
        return max((float) $this->total - $this->amount_paid, 0);
    }

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($invoice) {
    //         $invoice->invoice_code =
    //             'INV-' . strtoupper(Str::random(8));
    //     });
    // }
}
