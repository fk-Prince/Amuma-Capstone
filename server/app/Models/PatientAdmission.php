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
        'branch_contract_id',
        'bed_id',
        'patient_id',
        'status',
        'note',
        'admitted_at',
        'end_date',
        'booking_id',
    ];

    protected $casts = [
        'admitted_at' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function admissionContract()
    {
        return $this->belongsTo(
            BranchContract::class,
            'branch_contract_id',
            'branch_contract_id'
        );
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class, 'bed_id', 'bed_id');
    }

    public function patient()
    {
        return $this->belongsTo(
            Patient::class,
            'patient_id',
            'patient_id'
        );
    }

    public function bookings()
    {
        return $this->belongsTo(
            Booking::class,
            'booking_id',
            'booking_id'
        );
    }

    public function invoiceAdmission()
    {
        return $this->hasMany(
            InvoiceFacility::class,
            'patient_admission_id',
            'patient_admission_id'
        );
    }

    public function currentInvoiceFacility()
    {
        $now = now();

        return $this->hasOne(
            InvoiceFacility::class,
            'patient_admission_id',
            'patient_admission_id'
        )
            ->where('start_date', '<=', $now)
            ->where(function ($query) use ($now) {
                $query
                    ->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            })
            ->orderByDesc('start_date');
    }
}
