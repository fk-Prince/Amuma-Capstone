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

    public function paginateForEmployee(int $branchId, int $employeeId, int $perPage)
    {
        return $this->query($branchId)
            ->where('employee_id', $employeeId)
            ->paginate($perPage);
    }

    public function paginateForPatient(int $branchId, int $patientId, int $perPage)
    {
        return $this->query($branchId)
            ->where('patient_id', $patientId)
            ->paginate($perPage);
    }

    public function forSchedule(int $branchId, int $scheduleId)
    {
        return $this->query($branchId)
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

    public function isAdmitted(Patient $patient): bool
    {
        return $patient->currentAdmission()->exists();
    }

    public function wasAssigned(int $employeeId, int $scheduleId): bool
    {
        return ScheduleAssigned::where('employee_id', $employeeId)
            ->whereHas('scheduleService', fn($service) => $service->where('schedule_id', $scheduleId))
            ->exists();
    }

    public function schedulesOf(int $employeeId, int $branchId, int $limit = 50)
    {
        return Schedule::whereHas('patient', fn($patient) => $patient->where('branch_id', $branchId))
            ->whereHas(
                'scheduleServices.assigned',
                fn($assigned) => $assigned->where('employee_id', $employeeId)
            )
            ->with('patient:patient_id,first_name,last_name,suffix')
            ->select(['schedule_id', 'schedule_code', 'patient_id', 'scheduled_at'])
            ->orderByDesc('scheduled_at')
            ->limit($limit)
            ->get();
    }

    public function admittedPatients(int $branchId, int $limit = 200)
    {
        return Patient::where('branch_id', $branchId)
            ->whereHas('currentAdmission')
            ->select(['patient_id', 'uuid', 'first_name', 'last_name', 'suffix'])
            ->orderBy('first_name')
            ->limit($limit)
            ->get();
    }

    private function query(int $branchId)
    {
        return EmployeeNote::where('branch_id', $branchId)
            ->with($this->relations())
            ->orderByDesc('employee_note_id');
    }

    private function relations(): array
    {
        return [
            'employee:employee_id,user_id,first_name,middle_name,last_name',
            'employee.users:user_id,uuid',
            'schedule:schedule_id,schedule_code,scheduled_at',
            'patient:patient_id,uuid,first_name,last_name,suffix',
            'recorder:employee_id,first_name,middle_name,last_name',
        ];
    }
}
