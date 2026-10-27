<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class ScheduleAssigned extends Model
{
    use Auditable;

    protected string $auditModule = 'schedules';

    protected $primaryKey = 'schedule_assigned_id';
    protected $table = "schedule_assigned";
    protected $fillable = [
        'schedule_services_id',
        'employee_id',
        'note',
        'start_time',
        'end_time',
        'is_active'
    ];

    public function auditBranchId(): ?int
    {
        $branchId = $this->auditSchedule()?->patient()->value('branch_id');

        return is_numeric($branchId) ? (int) $branchId : null;
    }

    public function auditDescription(string $action): ?string
    {
        $employee = trim((string) Employee::where('employee_id', $this->employee_id)->first()?->full_name) ?: 'an employee';
        $code = $this->auditSchedule()?->schedule_code ?? 'a schedule';

        if ($action === 'created') {
            return "Assigned {$employee} to schedule {$code}.";
        }

        if ($this->wasChanged('is_active')) {
            return $this->is_active
                ? "Assigned {$employee} to schedule {$code}."
                : "Removed {$employee} from schedule {$code}.";
        }

        if ($this->wasChanged(['start_time', 'end_time'])) {
            return "Changed {$employee}'s hours on schedule {$code}.";
        }

        if ($this->wasChanged('note')) {
            return "Updated {$employee}'s note on schedule {$code}.";
        }

        return "Updated {$employee}'s assignment on schedule {$code}.";
    }

    private function auditSchedule(): ?Schedule
    {
        return Schedule::whereHas(
            'scheduleServices',
            fn($query) => $query->where('schedule_services_id', $this->schedule_services_id)
        )->first();
    }

    public function scheduleService()
    {
        return $this->belongsTo(ScheduleService::class, 'schedule_services_id', 'schedule_services_id');
    }

    public function adlAttendances()
    {
        return $this->hasMany(AdlAttendance::class, 'schedule_assigned_id', 'schedule_assigned_id');
    }

    public function employee()
    {
        return $this->belongsTo(EmployeeBranch::class, 'employee_id', 'employee_id');
    }

    public function scopeActive(mixed $query)
    {
        return $query->where('is_active', true);
    }
}
