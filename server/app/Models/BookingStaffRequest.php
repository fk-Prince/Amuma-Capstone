<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingStaffRequest extends Model
{
    protected $table = 'booking_staff_requests';
    protected $primaryKey = 'booking_staff_request_id';

    protected $fillable = [
        'booking_id',
        'employee_id',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
