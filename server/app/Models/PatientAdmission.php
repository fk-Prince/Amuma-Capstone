<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientAdmission extends Model
{
    use Auditable, HasFactory;

    protected string $auditModule = 'admissions';

    protected $primaryKey = 'patient_admission_id';
    public const STATUS_ADMITTED = 'admitted';
    public const STATUS_DISCHARGED = 'discharged';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'patient_id',
        'bed_id',
        'status',
        'note',
        'admitted_at',
        'discharged_at',
    ];

    protected $casts = [
        'admitted_at' => 'datetime',
        'discharged_at' => 'datetime',
    ];


    public function auditBranchId(): ?int
    {
        $branchId = Patient::where('patient_id', $this->patient_id)->value('branch_id');

        return is_numeric($branchId) ? (int) $branchId : null;
    }

    public function auditDescription(string $action): ?string
    {
        $name = trim((string) $this->patient?->display_name) ?: 'a patient';

        if ($action === 'created') {
            return $this->status === self::STATUS_ADMITTED
                ? "Admitted {$name}."
                : "Registered an admission for {$name}.";
        }

        if ($this->wasChanged('status')) {
            return match ($this->status) {
                self::STATUS_ADMITTED => "Admitted {$name}.",
                self::STATUS_DISCHARGED => "Discharged {$name}.",
                self::STATUS_CANCELLED => "Cancelled the admission of {$name}.",
                default => null,
            };
        }

        if ($this->wasChanged('bed_id')) {
            return "Moved {$name} to another bed.";
        }

        if ($this->wasChanged('discharged_at')) {
            return "Changed the end of {$name}'s stay.";
        }

        return null;
    }

    public function getEndDateAttribute()
    {
        return $this->discharged_at;
    }

    public function roomTransfers()
    {
        return $this->hasMany(
            RoomTransfer::class,
            'patient_admission_id',
            'patient_admission_id'
        );
    }

    public function patient()
    {
        return $this->belongsTo(
            Patient::class,
            'patient_id',
            'patient_id'
        );
    }

    public function chargeDiagnoses()
    {
        return $this->hasMany(
            AdmissionChargeDiagnosis::class,
            'patient_admission_id',
            'patient_admission_id'
        );
    }

    public function periods()
    {
        return $this->hasMany(
            AdmissionPeriod::class,
            'patient_admission_id',
            'patient_admission_id'
        );
    }


    public function currentPeriod()
    {
        return $this->hasOne(
            AdmissionPeriod::class,
            'patient_admission_id',
            'patient_admission_id'
        )
            ->whereNotIn('status', AdmissionPeriod::CLOSED_STATUSES)
            // The period being lived in is the earliest one still running.
            // Ordering by id alone put a prepaid future period ahead of it
            // whenever an accommodation change created the current one later.
            ->orderByRaw(
                "CASE WHEN end_date IS NULL OR end_date > now() THEN 0 ELSE 1 END"
            )
            ->orderByRaw(
                "CASE WHEN reason = ? THEN 1 ELSE 0 END",
                [AdmissionPeriod::REASON_EXTENDED]
            )
            ->orderBy('start_date')
            ->orderBy('admission_period_id');
    }

    public function futurePeriods()
    {
        return $this->hasMany(
            AdmissionPeriod::class,
            'patient_admission_id',
            'patient_admission_id'
        )
            ->whereNotIn('status', AdmissionPeriod::CLOSED_STATUSES)
            ->where('reason', AdmissionPeriod::REASON_EXTENDED)
            ->orderBy('admission_period_id');
    }

    public function latestPeriod()
    {
        return $this->hasOne(
            AdmissionPeriod::class,
            'patient_admission_id',
            'patient_admission_id'
        )->latestOfMany('admission_period_id');
    }


    public function bed()
    {
        return $this->belongsTo(Bed::class, 'bed_id', 'bed_id');
    }

    public function caregiverShifts()
    {
        return $this->hasMany(
            CaregiverShift::class,
            'admission_id',
            'patient_admission_id'
        );
    }

    public function additionalCharges()
    {
        return $this->hasMany(AdditionalCharge::class, 'patient_id', 'patient_id');
    }

    public function invoiceAdmission()
    {
        return $this->hasManyThrough(
            InvoiceAdmission::class,
            AdmissionPeriod::class,
            'patient_admission_id',
            'admission_period_id',
            'patient_admission_id',
            'admission_period_id'
        );
    }

    public function currentInvoiceAdmission()
    {
        return $this->hasOneThrough(
            InvoiceAdmission::class,
            AdmissionPeriod::class,
            'patient_admission_id',
            'admission_period_id',
            'patient_admission_id',
            'admission_period_id'
        )
            ->whereNotIn('admission_periods.status', AdmissionPeriod::CLOSED_STATUSES)
            ->orderByRaw(
                "CASE WHEN admission_periods.end_date IS NULL"
                    . " OR admission_periods.end_date > now() THEN 0 ELSE 1 END"
            )
            ->orderByRaw(
                "CASE WHEN admission_periods.reason = ? THEN 1 ELSE 0 END",
                [AdmissionPeriod::REASON_EXTENDED]
            )
            ->orderBy('admission_periods.start_date')
            ->orderBy('admission_periods.admission_period_id');
    }
}
