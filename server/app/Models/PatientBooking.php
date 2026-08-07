<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'booking_id',
        'invoice_id',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
