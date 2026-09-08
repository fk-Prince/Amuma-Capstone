<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientAdmission extends Model
{
    use HasFactory;

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

    // discharged_at is the date the stay is planned to end under the billing
    // plan — an extension pushes it out, and an early discharge overwrites it
    // with the actual date. Exposed under the old name so existing reads and
    // API payloads keep working.
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
