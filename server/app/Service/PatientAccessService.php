<?php

namespace App\Service;

use App\Repository\PatientAccessRepository;
use App\Http\Resources\PatientAccessResource;
use App\Models\User;

class PatientAccessService
{
    private PatientAccessRepository $patientAccessRepository;

    public function __construct(PatientAccessRepository $patientAccessRepository)
    {
        $this->patientAccessRepository = $patientAccessRepository;
    }


    public function overview(array $payload)
    {
        return $this->patientAccessRepository->overview($payload);
    }

    public function scheduleList(array $payload)
    {
        return $this->patientAccessRepository->scheduleList($payload);
    }

    public function bookings(array $payload)
    {
        return $this->patientAccessRepository->bookings($payload);
    }
}
