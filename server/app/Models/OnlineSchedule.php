<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineSchedule extends Model
{
    protected $primaryKey = 'online_schedule_id';
    public $timestamps = false;
    protected $fillable = [
        'schedule_assigned_id',
        'qr_in_token',
        'qr_out_token',
        'in_timestamp',
        'out_timestamp',
        'notes',
    ];

    protected $casts = [
        'in_timestamp' => 'datetime',
        'out_timestamp' => 'datetime',
    ];


    public function assigned()
    {
        return $this->belongsTo(
            ScheduleAssigned::class,
            'schedule_assigned_id',
            'schedule_assigned_id'
        );
    }
}
