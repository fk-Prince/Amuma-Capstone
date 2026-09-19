<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    public const STATUS_PENDING = 'unpaid';
    public const STATUS_PARTIAL = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_VOID = 'void';
    public const STATUS_WRITTEN_OFF = 'written_off';

    public const CLOSED_STATUSES = [self::STATUS_VOID, self::STATUS_WRITTEN_OFF];

    protected $primaryKey = 'invoice_id';

    public $timestamps = false;

    protected $fillable = [
        'total_amount',
        'branch_id',
        'invoice_code',
        'status',
        'voided_at',
        'voided_by',
        'void_reason',
        'written_off_at',
        'written_off_by',
        'write_off_reason',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'voided_at' => 'datetime',
        'written_off_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(
            Branch::class,
            'branch_id',
            'branch_id'
        );
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by', 'user_id');
    }

    public function writtenOffBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'written_off_by', 'user_id');
    }

    public function getIsVoidedAttribute(): bool
    {
        return $this->status === self::STATUS_VOID;
    }

    public function getIsWrittenOffAttribute(): bool
    {
        return $this->status === self::STATUS_WRITTEN_OFF;
    }

    public function invoiceServices(): HasMany
    {
        return $this->hasMany(InvoiceServices::class, 'invoice_id', 'invoice_id');
    }

    public function invoiceAdmissionLines(): HasMany
    {
        return $this->hasMany(InvoiceAdmission::class, 'invoice_id', 'invoice_id');
    }


    public function paymentDescription(): string
    {
        $stay = $this->invoiceAdmissionLines
            ->sortByDesc('invoice_admission_id')
            ->first();

        if ($stay?->description) {
            return $stay->description;
        }

        $services = $this->invoiceServices
            ->pluck('description')
            ->filter()
            ->unique()
            ->values();

        if ($services->isNotEmpty()) {
            return $services->implode(', ');
        }

        return 'Payment for balance';
    }

    public function adlHoursBooked(): ?float
    {
        $hours = $this->invoiceServices
            ->filter(fn($line) => $line->scheduleService?->service_id === null)
            ->sum(fn($line) => (float) ($line->scheduleService?->hours_booked ?? 0));

        return $hours > 0 ? $hours : null;
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(
            PaymentInvoiceAllocation::class,
            'invoice_id',
            'invoice_id'
        );
    }

    public function payments()
    {
        return $this->belongsToMany(
            Payment::class,
            'payment_invoice_allocation',
            'invoice_id',
            'payment_id',
            'invoice_id',
            'payment_id'
        )->withPivot(['allocation_id', 'amount', 'description']);
    }

    public function invoiceAdjustments(): HasMany
    {
        return $this->hasMany(InvoiceAdjustment::class, 'invoice_id',    'invoice_id');
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

    public function getAmountPaidAttribute()
    {
        return round((float) $this->allocations->sum('amount'), 2);
    }


    public function refundAllocations()
    {
        return $this->hasManyThrough(
            RefundAllocation::class,
            PaymentInvoiceAllocation::class,
            'invoice_id',
            'allocation_id',
            'invoice_id',
            'allocation_id'
        );
    }


    public function getRefundedAmountAttribute()
    {
        return round(
            (float) $this->allocations
                ->flatMap(
                    fn(PaymentInvoiceAllocation $allocation) => $allocation->refundAllocations
                )
                ->sum('amount'),
            2
        );
    }

    public function getNetPaidAmountAttribute()
    {
        return round(max($this->amount_paid - $this->refunded_amount,  0),  2);
    }


    public function syncStatus()
    {
        if (in_array($this->status, self::CLOSED_STATUSES, true)) {
            return $this;
        }

        $this->load('invoiceAdjustments', 'allocations.refundAllocations.refund.transaction');

        $status = match (true) {
            $this->balance_due <= 0 => self::STATUS_PAID,
            $this->net_paid_amount > 0 => self::STATUS_PARTIAL,
            default => self::STATUS_PENDING,
        };

        if ($status !== $this->status) {
            $this->update(['status' => $status]);
        }

        return $this;
    }

    public function getLatestAdjustmentAttribute()
    {
        $adjustment = $this->invoiceAdjustments
            ->sortByDesc('created_at')
            ->first();

        return round((float) ($adjustment?->amount ?? 0), 2);
    }


    public function getTotalAdjustmentAttribute()
    {
        return round((float) $this->invoiceAdjustments->sum('amount'),  2);
    }

    public function getAdjustedTotalAttribute()
    {
        return round((float) $this->total_amount + $this->total_adjustment, 2);
    }

    public function getBalanceDueAttribute()
    {
        if (in_array($this->status, self::CLOSED_STATUSES, true)) {
            return 0.0;
        }

        return round(max($this->adjusted_total - $this->net_paid_amount, 0),      2);
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


    private static array $patientInvoiceIdCache = [];

    public static function patientInvoiceIds(mixed $patientId)
    {
        $key = (string) $patientId;

        if (array_key_exists($key, self::$patientInvoiceIdCache)) {
            return self::$patientInvoiceIdCache[$key];
        }

        $admission = InvoiceAdmission::whereHas(
            'admissionPeriod.patientAdmission',
            fn($query) => $query->where('patient_id', $patientId)
        )->pluck('invoice_id');

        $scheduled = InvoiceServices::whereHas(
            'scheduleService.schedule',
            fn($query) => $query->where('patient_id', $patientId)
        )->pluck('invoice_id');

        return self::$patientInvoiceIdCache[$key] = $admission
            ->merge($scheduled)
            ->unique()
            ->values();
    }

    public static function forgetPatientInvoiceIds(): void
    {
        self::$patientInvoiceIdCache = [];
    }
}
