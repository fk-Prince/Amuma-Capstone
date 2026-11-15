<?php

namespace App\Repository;

use App\Models\Vital;

class VitalRepository
{
    public function create(array $payload): Vital
    {
        return Vital::create($payload);
    }

    public function update(Vital $vital, array $payload): Vital
    {
        $vital->update($payload);

        return $vital->fresh();
    }

    public function paginateForPatient(int $patientId, int $perPage)
    {
        return Vital::where('patient_id', $patientId)
            ->with('recordedBy')
            ->orderByDesc('recorded_date')
            ->orderByDesc('recorded_time')
            ->paginate($perPage);
    }

    public function findForPatient(mixed $vitalId, string $patientUuid, mixed $branchId): ?Vital
    {
        return Vital::whereHas(
            'patient',
            fn($query) => $query->where('uuid', $patientUuid)->where('branch_id', $branchId)
        )->find($vitalId);
    }
}
