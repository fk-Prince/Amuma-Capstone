<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $primaryKey = 'schedule_id';


    protected $fillable = [
        'patient_id',
        'scheduled_location_id',
        'scheduled_at',
        'status',
        'category',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'scheduled_location_id', 'location_id');
    }
    public function scheduleServices()
    {
        return $this->hasMany(ScheduleService::class, 'schedule_id', 'schedule_id');
    }
}
