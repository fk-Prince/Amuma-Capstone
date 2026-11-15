<?php

namespace App\Repository;

use App\Models\Medication;
use App\Models\MedicationSchedule;

class MedicationRepository
{
    public function create(array $payload): Medication
    {
        return Medication::create($payload);
    }

    public function update(Medication $medication, array $payload): Medication
    {
        $medication->update($payload);

        return $medication->fresh();
    }

    public function paginateForPatient(int $patientId, int $perPage)
    {
        return Medication::where('patient_id', $patientId)
            ->with(['schedules', 'recordedBy'])
            ->orderByDesc('recorded_at')
            ->paginate($perPage);
    }

    public function findForPatient(mixed $medicationId, string $patientUuid, mixed $branchId): ?Medication
    {
        return Medication::whereHas(
            'patient',
            fn($query) => $query->where('uuid', $patientUuid)->where('branch_id', $branchId)
        )->find($medicationId);
    }

    public function findSchedule(Medication $medication, mixed $scheduleId): ?MedicationSchedule
    {
        return $medication->schedules()
            ->where('medication_schedule_id', $scheduleId)
            ->first();
    }

    public function findScheduleAt(Medication $medication, string $date, string $time): ?MedicationSchedule
    {
        return $medication->schedules()
            ->whereDate('date', $date)
            ->where('time', $time)
            ->first();
    }

    public function createSchedule(Medication $medication, array $payload): MedicationSchedule
    {
        return $medication->schedules()->create($payload);
    }
}
