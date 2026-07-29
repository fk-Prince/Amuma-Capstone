<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    //
    protected $primaryKey = 'booking_id';
    protected $keyType = 'int';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'reference_id',
        'user_id',
        'branch_id',
        'booking_data',
        'status',
        'category',
        'valid_until',
    ];


    protected static function booted()
    {
        static::created(function ($booking) {
            $booking->updateQuietly([
                'reference_id' => 'BKN-' . str_pad(
                    $booking->booking_id,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),
            ]);
        });
    }

    public function user()
    {
        return $this->hasOne(User::class, 'user_id', 'user_id');
    }
    public function branch()
    {
        return $this->hasOne(Branch::class, 'branch_id', 'branch_id');
    }

    public function patientBooking()
    {
        return $this->hasMany(PatientBooking::class, 'booking_id', 'booking_id');
    }

    protected $casts = [
        'booking_data' => 'array',
    ];
}
