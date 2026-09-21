<?php

namespace App\Repository;

use App\Models\CaregiverFacilityShift;
use App\Models\Employee;
use App\Models\PatientAdmission;

class CaregiverShiftRepository
{
    public function findAdmission(int $admissionId): ?PatientAdmission
    {
        return PatientAdmission::with('patient')->find($admissionId);
    }

    public function forAdmission(int $admissionId)
    {
        return CaregiverFacilityShift::with('caregiver')
            ->where('admission_id', $admissionId)
            ->orderByDesc('is_active')
            ->orderBy('start_time')
            ->orderBy('caregiver_facility_shift_id')
            ->get();
    }

    public function find(int $shiftId): ?CaregiverFacilityShift
    {
        return CaregiverFacilityShift::with(['caregiver', 'admission.patient'])->find($shiftId);
    }

    public function create(array $attributes): CaregiverFacilityShift
    {
        return CaregiverFacilityShift::create($attributes)->load('caregiver');
    }

    public function facilityCaregivers(int $branchId)
    {
        return Employee::query()
            ->where('status', Employee::STATUS_ACTIVE)
            ->whereHas('employeeBranch', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)
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

        return CaregiverFacilityShift::query()
            ->join(
                'patient_admissions',
                'patient_admissions.patient_admission_id',
                '=',
                'caregiver_facility_shifts.admission_id'
            )
            ->where('caregiver_facility_shifts.is_active', true)
            ->where('patient_admissions.status', PatientAdmission::STATUS_ADMITTED)
            ->whereIn('caregiver_facility_shifts.caregiver_id', $employeeIds)
            ->selectRaw('caregiver_facility_shifts.caregiver_id as caregiver_id, COUNT(DISTINCT caregiver_facility_shifts.admission_id) as residents')
            ->groupBy('caregiver_facility_shifts.caregiver_id')
            ->pluck('residents', 'caregiver_id')
            ->map(fn($count) => (int) $count)
            ->all();
    }

    public function dutyWindows(array $employeeIds): array
    {
        if (!$employeeIds) {
            return [];
        }

        return CaregiverFacilityShift::query()
            ->join(
                'patient_admissions',
                'patient_admissions.patient_admission_id',
                '=',
                'caregiver_facility_shifts.admission_id'
            )
            ->where('caregiver_facility_shifts.is_active', true)
            ->where('patient_admissions.status', PatientAdmission::STATUS_ADMITTED)
            ->whereIn('caregiver_facility_shifts.caregiver_id', $employeeIds)
            ->get([
                'caregiver_facility_shifts.caregiver_id',
                'caregiver_facility_shifts.start_time',
                'caregiver_facility_shifts.end_time',
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

    public function hasActiveAssignment(int $admissionId, int $caregiverId, ?int $exceptShiftId = null): bool
    {
        return CaregiverFacilityShift::where('admission_id', $admissionId)
            ->where('caregiver_id', $caregiverId)
            ->where('is_active', true)
            ->when($exceptShiftId, fn($query) => $query->where('caregiver_facility_shift_id', '!=', $exceptShiftId))
            ->exists();
    }

    public function activeCountForAdmission(int $admissionId): int
    {
        return CaregiverFacilityShift::where('admission_id', $admissionId)
            ->where('is_active', true)
            ->count();
    }
}
