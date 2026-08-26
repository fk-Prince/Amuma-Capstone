<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $primaryKey = 'booking_id';
    protected $keyType = 'int';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';


    public const CATEGORY_ONLINE = 'homecare';
    public const CATEGORY_FACILITY = 'facility';

    public const BOOKINGTYPE_ONLINE = 'online';
    public const BOOKINGTYPE_WALKIN = 'walk_in';


    public const TYPE_PREADMISSION = 'Pre-Admission';
    public const TYPE_COMPLETEADMISSION = 'Complete';
    public const TYPE_WALKINADMISSION = 'Walk-in Admission';

    public const TYPE_ADL = 'ADL';
    public const TYPE_MEDICAL = 'Medical';


    protected $fillable = [
        'reference_id',
        'user_id',
        'branch_id',
        'booking_data',
        'status',
        'reason',
        'category',
        'valid_until',
        'booking_type'
    ];


    protected static function booted()
    {
        static::creating(function ($booking) {
            if (!$booking->reference_id) {
                $lastBooking = self::whereNotNull('reference_id')
                    ->orderByDesc('booking_id')
                    ->first();

                $nextNumber = 1;

                if ($lastBooking && $lastBooking->reference_id) {
                    $lastNumber = (int) substr($lastBooking->reference_id, 4);
                    $nextNumber = $lastNumber + 1;
                }
                $booking->reference_id = 'BKN-' . str_pad(
                    $nextNumber,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    public function patientsBooking()
    {
        return $this->hasManyThrough(
            Patient::class,
            PatientBooking::class,
            'booking_id',
            'patient_id',
            'booking_id',
            'patient_id'
        );
    }

    public function patientBookings()
    {
        return $this->hasMany(PatientBooking::class, 'booking_id', 'booking_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'user_id', 'user_id');
    }
    public function branch()
    {
        return $this->hasOne(Branch::class, 'branch_id', 'branch_id');
    }

    public function patientAdmissionBookings()
    {
        return $this->hasMany(PatientAdmission::class, 'booking_id', 'booking_id');
    }

    public function patientScheduleBookings()
    {
        return $this->hasMany(Schedule::class, 'booking_id', 'booking_id');
    }

    public function reservedBedId()
    {
        return data_get($this->booking_data, 'reserved.bed.bed_id');
    }

    protected $casts = [
        'booking_data' => 'array',
    ];
}
