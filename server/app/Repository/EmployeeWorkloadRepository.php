<?php

namespace App\Repository;

use App\Models\CaregiverShift;
use App\Models\EmployeeBranch;
use App\Models\PatientAdmission;
use App\Models\Schedule;
use App\Models\ScheduleAssigned;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EmployeeWorkloadRepository
{
    public function assignments(EmployeeBranch $employeeBranch, ?Carbon $before = null): Collection
    {
        return ScheduleAssigned::query()
            ->where('employee_id', $employeeBranch->employee_id)
            ->where('is_active', true)
            ->whereHas('scheduleService.schedule', fn($schedule) => $schedule
                ->whereIn('status', [Schedule::STATUS_PENDING, Schedule::STATUS_ONGOING])
                ->whereHas('patient', fn($patient) => $patient->where('branch_id', $employeeBranch->branch_id))
                ->when($before, fn($query) => $query->where('scheduled_at', '<', $before)))
            ->with([
                'scheduleService:schedule_services_id,schedule_id,service_id,type',
                'scheduleService.service:service_id,service_name',
                'scheduleService.schedule:schedule_id,schedule_code,patient_id,scheduled_at,status',
                'scheduleService.schedule.patient:patient_id,first_name,last_name',
            ])
            ->withExists(['attendances as clocked_in' => fn($attendance) => $attendance
                ->whereNotNull('in_timestamp')
                ->whereNull('out_timestamp')])
            ->get()
            ->sortBy(fn(ScheduleAssigned $assigned) => $assigned->scheduleService?->schedule?->scheduled_at)
            ->values();
    }

    public function shifts(EmployeeBranch $employeeBranch): Collection
    {
        return CaregiverShift::query()
            ->where('caregiver_id', $employeeBranch->employee_id)
            ->where('is_active', true)
            ->whereHas('admission', fn($admission) => $admission
                ->where('status', PatientAdmission::STATUS_ADMITTED)
                ->whereHas('bed.room', fn($room) => $room->where('branch_id', $employeeBranch->branch_id)))
            ->with([
                'admission.patient:patient_id,first_name,last_name',
                'admission.bed:bed_id,room_id',
                'admission.bed.room:room_id,room_no',
            ])
            ->get();
    }

    public function release(Collection $assignments, Collection $shifts): void
    {
        $assignments->each(fn(ScheduleAssigned $assigned) => $assigned->update(['is_active' => false]));
        $shifts->each(fn(CaregiverShift $shift) => $shift->update(['is_active' => false]));
    }
}
