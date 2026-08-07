<?php

namespace App\Models;

use App\Models\Location;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $primaryKey = 'client_id';


    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'location_id',
        'phone_number',
        'is_verified',
        'avatar',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }
    public function patientAccess()
    {
        return $this->hasMany(PatientAccess::class,  'client_id', 'client_id');
    }

    public function bookings()
    {
        return $this->hasManyThrough(
            Booking::class,
            User::class,
            'user_id',
            'user_id',
            'user_id',
            'user_id'
        );
    }
}
