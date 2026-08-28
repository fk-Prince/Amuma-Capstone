<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasUuids;

    protected $primaryKey = 'patient_id';

    protected $fillable = [
        'branch_id',
        'location_id',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'height',
        'weight',
        'blood_type',
        'date_of_birth',
        'phone_number',
        'citizenship',
        'assessment',
        'allergies',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'assessment' => 'array',
        'allergies' => 'array',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'patient_id', 'patient_id');
    }

    public function medications()
    {
        return $this->hasMany(Medication::class, 'patient_id', 'patient_id');
    }

    public function vitals()
    {
        return $this->hasMany(Vital::class, 'patient_id', 'patient_id');
    }

    public function activities()
    {
        return $this->hasMany(PatientActivity::class, 'patient_id', 'patient_id');
    }

    public function admissions()
    {
        return $this->hasMany(PatientAdmission::class, 'patient_id', 'patient_id');
    }

    public function currentAdmission()
    {
        $now = now();

        return $this->hasOne(PatientAdmission::class, 'patient_id', 'patient_id')
            ->whereIn('status', ['admitted', 'waiting'])
            ->where(function ($query) use ($now) {
                $query
                    ->where('status', 'waiting')
                    ->orWhere(function ($query) use ($now) {
                        $query
                            ->where('status', 'admitted')
                            ->whereHas('invoiceAdmission', function ($query) use ($now) {
                                $query
                                    ->where('start_date', '<=', $now)
                                    ->where(function ($q) use ($now) {
                                        $q->whereNull('end_date')
                                            ->orWhere('end_date', '>=', $now);
                                    });
                            });
                    });
            })
            ->orderByRaw("CASE WHEN status = 'admitted' THEN 1 ELSE 2 END")
            ->latestOfMany('patient_admission_id');
    }


    public function latestAdmission()
    {
        return $this->hasOne(PatientAdmission::class, 'patient_id', 'patient_id')
            ->latestOfMany('patient_admission_id');
    }

    public function latestHomecareSchedule()
    {
        return $this->hasOne(Schedule::class, 'patient_id', 'patient_id')
            ->where('category', Schedule::CATEGORYHOMECARE)
            ->whereIn('status', [Schedule::STATUS_PENDING, Schedule::STATUS_ONGOING])
            ->latestOfMany('scheduled_at');
    }

    public function getBillingSummaryAttribute(): array
    {
        $invoiceIds = $this->getPatientInvoiceIds();

        if ($invoiceIds->isEmpty()) {
            return [
                'balance_due' => 0.0,
                'total_paid' => 0.0,
                'refundable' => 0.0,
                'adjusted' => 0.0,
            ];
        }

        $invoices = Invoice::whereIn('invoice_id', $invoiceIds)
            ->whereIn('status', [
                Invoice::STATUS_PENDING,
                Invoice::STATUS_PARTIAL,
                Invoice::STATUS_PAID,
            ])
            ->with(['payments.refunds', 'invoiceAdjustments'])
            ->get();

        return [
            'balance_due' => (float) $invoices->sum('balance_due'),
            'total_paid' => (float) $invoices->sum('amount_paid'),
            'refundable' => (float) $invoices->sum('refunded_processing_amount'),
            // Each invoice's own adjusted_total accessor already falls back
            // to its total when there's no adjustment row, so summing that
            // (rather than raw invoice_adjustments amounts) gives the real
            // post-adjustment total instead of silently collapsing to 0.
            'adjusted' => (float) $invoices->sum('adjusted_total'),
        ];
    }

    public function getPatientInvoicesAttribute()
    {
        $invoiceIds = $this->getPatientInvoiceIds();

        if ($invoiceIds->isEmpty()) {
            return collect();
        }

        return Invoice::whereIn('invoice_id', $invoiceIds)
            ->with([
                'payments.refunds',
                'invoiceAdjustments',
                'invoiceServices.scheduleService.service',
                'invoiceServices.scheduleService.schedule',
                'invoiceAccommodation.patientAdmission',
                'invoiceAccommodation.branchContract',
            ])
            ->orderByDesc('invoice_id')
            ->get()
            ->makeVisible([
                'amount_paid',
                'refunded_amount',
                'refunded_completed_amount',
                'refunded_processing_amount',
                'net_paid_amount',
                'adjusted_total',
                'balance_due',
                'refund_status',
            ]);
    }

    private function getPatientInvoiceIds()
    {
        $admissionInvoiceIds = InvoiceAccommodation::whereHas('patientAdmission', function ($query) {
            $query->where('patient_id', $this->patient_id);
        })->pluck('invoice_id');

        $scheduleInvoiceIds = InvoiceServices::whereHas('scheduleService.schedule', function ($query) {
            $query->where('patient_id', $this->patient_id);
        })->pluck('invoice_id');

        return $admissionInvoiceIds
            ->merge($scheduleInvoiceIds)
            ->unique()
            ->values();
    }
}
