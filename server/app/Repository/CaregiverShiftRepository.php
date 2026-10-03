<?php

namespace App\Repository;

use App\Models\CaregiverShift;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\PatientAdmission;

class CaregiverShiftRepository
{
    public function findAdmission(int $admissionId): ?PatientAdmission
    {
        return PatientAdmission::with('patient')->find($admissionId);
    }

    public function forAdmission(int $admissionId)
    {
        return CaregiverShift::with('caregiver')
            ->where('admission_id', $admissionId)
            ->orderByDesc('is_active')
            ->orderBy('start_time')
            ->orderBy('caregiver_shift_id')
            ->get();
    }

    public function find(int $shiftId): ?CaregiverShift
    {
        return CaregiverShift::with(['caregiver', 'admission.patient'])->find($shiftId);
    }

    public function create(array $attributes): CaregiverShift
    {
        return CaregiverShift::create($attributes)->load('caregiver');
    }

    public function facilityCaregivers(int $branchId)
    {
        return Employee::query()
            ->whereHas('employeeBranch', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                    ->where('status', EmployeeBranch::STATUS_ACTIVE)
                    ->where('role_name', 'caregiver')
                    ->whereIn('assignment_type', ['facility', 'both']);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public function isFacilityCaregiver(int $employeeId, int $branchId): bool
    {
        return $this->facilityCaregivers($branchId)->contains('employee_id', $employeeId);
    }

    public function residentCounts(array $employeeIds): array
    {
        if (!$employeeIds) {
            return [];
        }

        return CaregiverShift::query()
            ->join(
                'patient_admissions',
                'patient_admissions.patient_admission_id',
                '=',
                'caregiver_shifts.admission_id'
            )
            ->where('caregiver_shifts.is_active', true)
            ->where('patient_admissions.status', PatientAdmission::STATUS_ADMITTED)
            ->whereIn('caregiver_shifts.caregiver_id', $employeeIds)
            ->selectRaw('caregiver_shifts.caregiver_id as caregiver_id, COUNT(DISTINCT caregiver_shifts.admission_id) as residents')
            ->groupBy('caregiver_shifts.caregiver_id')
            ->pluck('residents', 'caregiver_id')
            ->map(fn($count) => (int) $count)
            ->all();
    }

    public function dutyWindows(array $employeeIds): array
    {
        if (!$employeeIds) {
            return [];
        }

        return CaregiverShift::query()
            ->join(
                'patient_admissions',
                'patient_admissions.patient_admission_id',
                '=',
                'caregiver_shifts.admission_id'
            )
            ->where('caregiver_shifts.is_active', true)
            ->where('patient_admissions.status', PatientAdmission::STATUS_ADMITTED)
            ->whereIn('caregiver_shifts.caregiver_id', $employeeIds)
            ->get([
                'caregiver_shifts.caregiver_id',
                'caregiver_shifts.start_time',
                'caregiver_shifts.end_time',
            ])
            ->groupBy('caregiver_id')
            ->map(fn($rows) => $rows
                ->map(fn($row) => [
                    'start_time' => substr((string) $row->start_time, 0, 5),
                    'end_time' => substr((string) $row->end_time, 0, 5),
                ])
                ->unique(fn($window) => $window['start_time'] . '-' . $window['end_time'])
                ->values()
                ->all())
            ->all();
    }

    public function branchBoard(int $branchId, ?int $caregiverId = null, string $search = '')
    {
        $term = '%' . $search . '%';

        return CaregiverShift::query()
            ->where('is_active', true)
            ->when($caregiverId !== null, fn($query) => $query->where('caregiver_id', $caregiverId))
            ->whereHas(
                'admission',
                fn($admission) => $admission
                    ->where('status', PatientAdmission::STATUS_ADMITTED)
                    ->whereHas('patient', fn($patient) => $patient->where('branch_id', $branchId))
            )
            ->when($search !== '', fn($query) => $query->where(
                fn($match) => $match
                    ->whereHas(
                        'caregiver',
                        fn($caregiver) => $caregiver->whereRaw("concat(first_name, ' ', last_name) ilike ?", [$term])
                    )
                    ->orWhereHas(
                        'admission.patient',
                        fn($patient) => $patient->whereRaw("concat(first_name, ' ', last_name) ilike ?", [$term])
                    )
                    ->orWhereHas('admission.bed.room', fn($room) => $room->where('room_no', 'ilike', $term))
            ))
            ->with(['caregiver', 'admission.patient', 'admission.bed.room'])
            ->orderBy('start_time')
            ->get();
    }

    public function uncoveredAdmissions(int $branchId, string $search = '')
    {
        $term = '%' . $search . '%';

        return PatientAdmission::query()
            ->where('status', PatientAdmission::STATUS_ADMITTED)
            ->whereHas('patient', fn($patient) => $patient->where('branch_id', $branchId))
            ->whereDoesntHave('caregiverShifts', fn($shift) => $shift->where('is_active', true))
            ->when($search !== '', fn($query) => $query->where(
                fn($match) => $match
                    ->whereHas(
                        'patient',
                        fn($patient) => $patient->whereRaw("concat(first_name, ' ', last_name) ilike ?", [$term])
                    )
                    ->orWhereHas('bed.room', fn($room) => $room->where('room_no', 'ilike', $term))
            ))
            ->with(['patient', 'bed.room'])
            ->orderBy('admitted_at')
            ->get();
    }

    public function hasActiveAssignment(int $admissionId, int $caregiverId, ?int $exceptShiftId = null): bool
    {
        return CaregiverShift::where('admission_id', $admissionId)
            ->where('caregiver_id', $caregiverId)
            ->where('is_active', true)
            ->when($exceptShiftId, fn($query) => $query->where('caregiver_shift_id', '!=', $exceptShiftId))
            ->exists();
    }

    public function activeCountForAdmission(int $admissionId): int
    {
        return CaregiverShift::where('admission_id', $admissionId)
            ->where('is_active', true)
            ->count();
    }
}
