<?php

namespace App\Repository;

use App\Models\AdditionalCharge;

class AdditionalChargeRepository
{
    public function create(array $payload)
    {
        return AdditionalCharge::create($payload);
    }

    public function paginateForPatient(int $patientId, int $perPage)
    {
        return AdditionalCharge::with(['invoice', 'patientAdmission'])
            ->whereHas('patientAdmission', fn($query) => $query->where('patient_id', $patientId))
            ->latest('additional_charge_id')
            ->paginate($perPage);
    }
}
