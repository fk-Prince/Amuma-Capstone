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
            InvoiceAccommodation::class,
            'patient_admission_id',
            'patient_admission_id'
        );
    }

    public function currentInvoiceAccommodation()
    {
        $now = now();

        return $this->hasOne(
            InvoiceAccommodation::class,
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
