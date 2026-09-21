<?php

namespace App\Models;

use App\Models\Concerns\CapitalizesNames;
use App\Repository\RefundRepository;
use App\Utils\InvoiceMoney;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use CapitalizesNames;

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
        'avatar',
        'allergies',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'allergies' => 'array',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return $this->avatar;
        }

        $name = trim("{$this->first_name} {$this->last_name}");

        return 'https://ui-avatars.com/api/?name=' . urlencode($name ?: 'Patient')
            . '&background=random&color=fff';
    }


    protected static function booted()
    {
        static::creating(function ($patient) {
            if ($patient->patient_code) {
                return;
            }

            $last = self::lockForUpdate()
                ->whereNotNull('patient_code')
                ->orderByDesc('patient_id')
                ->first();

            $next = $last
                ? ((int) substr($last->patient_code, 3)) + 1
                : 1;

            $patient->patient_code = 'PT-' . str_pad(
                (string) $next,
                6,
                '0',
                STR_PAD_LEFT
            );
        });
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

    public function assessments()
    {
        return $this->hasMany(PatientAssessment::class, 'patient_id', 'patient_id');
    }

    public function diagnoses()
    {
        return $this->hasMany(PatientDiagnosis::class, 'patient_id', 'patient_id');
    }

    public function admissions()
    {
        return $this->hasMany(PatientAdmission::class, 'patient_id', 'patient_id');
    }

    public function currentAdmission()
    {
        return $this->hasOne(PatientAdmission::class, 'patient_id', 'patient_id')
            ->whereIn('status', ['admitted', 'waiting'])
            ->where(function ($query) {
                $query
                    ->where('status', 'waiting')
                    ->orWhere(function ($query) {
                        $query
                            ->where('status', 'admitted')
                            ->whereHas('periods', function ($query) {
                                $query->whereNotIn(
                                    'status',
                                    AdmissionPeriod::CLOSED_STATUSES
                                );
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

    public function patientAccess()
    {
        return $this->hasMany(PatientAccess::class, 'patient_id', 'patient_id');
    }

    public function primaryGuardian()
    {
        return $this->hasOne(PatientAccess::class, 'patient_id', 'patient_id')
            ->where('have_access', true)
            ->oldestOfMany('patient_access_id');
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
                'pending_withdrawal' => 0.0,
                'adjusted' => 0.0,
                'accommodation_balance' => 0.0,
                'service_balance' => 0.0,
                'unpaid_invoice_count' => 0,
            ];
        }

        $invoices = Invoice::whereIn('invoice_id', $invoiceIds)
            ->whereIn('status', [
                Invoice::STATUS_PENDING,
                Invoice::STATUS_PARTIAL,
                Invoice::STATUS_PAID,
            ])
            ->with([
                'allocations.refundAllocations.refund.transaction',
                'invoiceAdjustments',
                'invoiceAdmissionLines',
                'invoiceServices',
            ])
            ->get();

        $voided = Invoice::whereIn('invoice_id', $invoiceIds)
            ->where('status', Invoice::STATUS_VOID)
            ->with(['allocations.refundAllocations.refund.transaction', 'invoiceAdjustments'])
            ->get();

        $accommodation = $invoices->filter(
            fn($invoice) => $invoice->invoiceAdmissionLines->isNotEmpty()
        );

        $service = $invoices->filter(
            fn($invoice) => $invoice->invoiceAdmissionLines->isEmpty()
                && $invoice->invoiceServices->isNotEmpty()
        );

        return [
            'balance_due' => (float) $invoices->sum('balance_due'),
            'total_paid' => (float) $invoices->sum('net_paid_amount'),
            'refundable' => app(RefundRepository::class)
                ->creditFor($this->patient_id),
            'pending_withdrawal' => app(RefundRepository::class)
                ->pendingCreditFor($this->patient_id),
            'adjusted' => (float) $invoices->sum('adjusted_total'),
            'accommodation_balance' => round((float) $accommodation->sum('balance_due'), 2),
            'service_balance' => round((float) $service->sum('balance_due'), 2),
            'unpaid_invoice_count' => $invoices
                ->filter(fn($invoice) => $invoice->balance_due > 0)
                ->count(),
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
                'allocations.refundAllocations.refund.transaction',
                'allocations.payment.transaction',
                'invoiceAdjustments',
                'invoiceServices.scheduleService.service',
                'invoiceServices.scheduleService.schedule',
                'invoiceAdmissionLines.admissionPeriod.patientAdmission',
                'invoiceAdmissionLines.admissionPeriod.branchContract',
            ])
            ->orderByDesc('invoice_id')
            ->get()
            ->makeVisible([
                'amount_paid',
                'refunded_amount',
                'net_paid_amount',
                'adjusted_total',
                'balance_due',
                'refund_status',
            ]);
    }

    private function getPatientInvoiceIds()
    {
        return Invoice::patientInvoiceIds($this->patient_id);
    }
}
