<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientBooking extends Model
{
    //

    protected $primaryKey = 'patient_booking_id';

    protected $fillable = [
        'booking_id',
        'patient_id'
    ];
}
