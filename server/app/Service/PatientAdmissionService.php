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
        private BranchContractRepository $branchContractRepository,
    ) {}

    public function registerPatientBed(array $payload)
    {
        $bed = $this->bedService->findAvailableBed($payload['bed_id']);

        $contract = $this->branchContractRepository->findByField([
            ['branch_id', '=', $payload['branch_id']],
            ['accommodation_type', '=', strtoupper($payload['plan'])],
            ['billing_cycle', '=', strtoupper($payload['billing_cycle'])],
        ]);

        if (!$contract) {
            throw new Exception('Contract not found.', 404);
        }

        $admissionDate = Carbon::parse($payload['admission_date']);

        $admission = $this->patientAdmissionRepository->create([
            'bed_id'      => $bed->bed_id,
            'patient_id'  => $payload['patient']['patient_id'],
            'status'      => 'admitted',
            'note'        => $payload['note'] ?? null,
            'admitted_at' => $admissionDate,
            'end_date'    => $this->calculateEndDate(
                $admissionDate,
                $contract->billing_cycle
            ),
        ]);

        if (!$admission) {
            throw new Exception('Unable to create patient admission.', 500);
        }

        $created = $admission->admissionContract()->create([
            'branch_contract_id' => $contract->branch_contract_id,
            'balance'            => 0,
        ]);

        if (!$created) {
            throw new Exception('Unable to create admission contract.', 500);
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
