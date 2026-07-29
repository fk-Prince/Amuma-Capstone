<?php

namespace App\Models;

use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $primaryKey = 'schedule_id';


    protected $fillable = [
        'patient_id',
        'scheduled_at',
        'status',
        'category',
        'schedule_code'
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


    protected static function boot()
    {
        parent::boot();

        static::created(function ($schedule) {
            $schedule->schedule_code =
                'SCH-' . str_pad(
                    $schedule->schedule_id,
                    6,
                    '0',
                    STR_PAD_LEFT
                );

            $schedule->saveQuietly();
        });
    }
}
