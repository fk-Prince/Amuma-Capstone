<?php

namespace App\Repository;

use App\Models\PatientAdmission;

class AdmissionRepository
{
    public function findByFields(array $conditions)
    {
        return PatientAdmission::where($conditions)->first();
    }
}
