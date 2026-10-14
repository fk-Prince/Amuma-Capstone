<?php

namespace App\Repository;

use App\Models\Employee;
use App\Models\EmployeeNote;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\ScheduleAssigned;

class EmployeeNoteRepository
{
    public function create(array $payload): EmployeeNote
    {
        return EmployeeNote::create($payload);
    }

    public function findInBranch(string $uuid, int $branchId): ?EmployeeNote
    {
        return EmployeeNote::where('uuid', $uuid)
            ->where('branch_id', $branchId)
            ->first();
    }

    public function paginateForEmployee(int $branchId, int $employeeId, int $viewerId, int $perPage)
    {
        return $this->visibleQuery($branchId, $viewerId)
            ->where('employee_id', $employeeId)
            ->paginate($perPage);
    }

    public function paginateForPatient(int $branchId, int $patientId, int $viewerId, int $perPage)
    {
        return $this->visibleQuery($branchId, $viewerId)
            ->where('patient_id', $patientId)
            ->paginate($perPage);
    }

    public function forSchedule(int $branchId, int $scheduleId, int $viewerId)
    {
        return $this->visibleQuery($branchId, $viewerId)
            ->where('schedule_id', $scheduleId)
            ->get();
    }

    public function load(EmployeeNote $note): EmployeeNote
    {
        return $note->load($this->relations());
    }

    public function findEmployee(string $uuid, int $branchId): ?Employee
    {
        return Employee::whereHas('users', fn($user) => $user->where('uuid', $uuid))
            ->whereHas('employeeBranch', fn($assignment) => $assignment->where('branch_id', $branchId))
            ->first();
    }

    public function findSchedule(string $code, int $branchId): ?Schedule
    {
        return Schedule::where('schedule_code', $code)
            ->whereHas('patient', fn($patient) => $patient->where('branch_id', $branchId))
            ->first();
    }

    public function findPatient(string $uuid, int $branchId): ?Patient
    {
        return Patient::where('uuid', $uuid)
            ->where('branch_id', $branchId)
            ->first();
    }

    public function isAssigned(int $employeeId, int $scheduleId): bool
    {
        return ScheduleAssigned::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->whereHas('scheduleService', fn($service) => $service->where('schedule_id', $scheduleId))
            ->exists();
    }

    private function visibleQuery(int $branchId, int $viewerId)
    {
        return EmployeeNote::where('branch_id', $branchId)
            ->where(function ($query) use ($viewerId) {
                $query->whereNotNull('schedule_id')
                    ->orWhereNotNull('patient_id')
                    ->orWhere('employee_id', $viewerId);
            })
            ->with($this->relations())
            ->orderByDesc('employee_note_id');
    }

    private function relations(): array
    {
        return [
            'employee:employee_id,user_id,first_name,last_name',
            'employee.users:user_id,uuid',
            'schedule:schedule_id,schedule_code,scheduled_at',
            'patient:patient_id,uuid,first_name,last_name,suffix',
        ];
    }
}
