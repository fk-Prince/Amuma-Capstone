<?php

namespace App\Service;

use App\Repository\BranchContractRepository;
use App\Repository\PatientAdmissionRepository;
use Carbon\Carbon;
use Exception;

class PatientAdmissionService
{
    public function __construct(
        private PatientAdmissionRepository $patientAdmissionRepository,
        private BedService $bedService,
    ) {}

    public function registerPatientBed(array $payload)
    {
        $bed = $this->bedService->findAvailableBed($payload['bed_id']);

        $admissionAt = Carbon::parse($payload['admitted_at']);

        $admission = $this->patientAdmissionRepository->create([
            'bed_id'      => $bed->bed_id,
            'patient_id'  => $payload['patient_id'],
            'status'      => 'admitted',
            'note'        => $payload['note'] ?? null,
            'admitted_at' => $admissionAt,
            'end_date'    => $this->calculateEndDate(
                $admissionAt,
                $payload['billing_cycle']
            ),
        ]);

        if (!$admission) {
            throw new Exception('Unable to create patient admission.', 500);
        }

        $bed->update([
            'status' => 'Occupied',
        ]);

        return $admission;
    }

    private function calculateEndDate(
        Carbon $admissionDate,
        string $billingCycle
    ): Carbon {
        return match (strtolower($billingCycle)) {
            'monthly' => $admissionDate->copy()->addMonth(),
            'yearly',
            'annual' => $admissionDate->copy()->addYear(),
            default => throw new Exception('Invalid billing cycle.', 422),
        };
    }
}
