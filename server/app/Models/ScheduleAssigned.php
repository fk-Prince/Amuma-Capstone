<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleAssigned extends Model
{
    protected $primaryKey = 'schedule_assigned_id';
    protected $table = "schedule_assigned";
    protected $fillable = [
        'schedule_services_id',
        'employee_id',
        'role',
    ];

    public function scheduleService()
    {
        return $this->belongsTo(ScheduleService::class, 'schedule_services_id', 'schedule_services_id');
    }

    public function onlineSchedules()
    {
        return $this->hasMany(OnlineSchedule::class, 'schedule_assigned_id', 'schedule_assigned_id');
    }

    public function employee()
    {
        return $this->belongsTo(EmployeeBranch::class, 'employee_id', 'employee_id');
    }
}
