<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineSchedule extends Model
{
    public const TYPE_SCANNED = 'scanned';
    public const TYPE_FORCE = 'force';

    protected $primaryKey = 'online_schedule_id';
    public $timestamps = false;
    protected $fillable = [
        'schedule_assigned_id',
        'type_in',
        'type_out',
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
        return $this->belongsTo(ScheduleAssigned::class, 'schedule_assigned_id',  'schedule_assigned_id');
    }
}
