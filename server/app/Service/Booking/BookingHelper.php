<?php

namespace App\Service\Booking;

use App\Repository\BranchContractRepository;
use App\Repository\ServiceRepository;
use App\Service\External\SupabaseService;
use Exception;

class BookingHelper
{
    public function __construct(
        private BranchContractRepository $branchContractRepository,
        private ServiceRepository $serviceRepository
    ) {}

    public  function getTotal(array $payload)
    {
        return match (strtolower($payload['category'])) {
            'homecare' => match ($payload['booking_data']['homecare']['type']) {
                'Medical' => $this->getMedicalTotal($payload),
                'ADL'     => $this->getAdlTotal($payload, $payload['branch_id']),
                default   => 0,
            },
            'facility' => $this->getFacilityTotal($payload,  $payload['branch_id']),
            default    => 0,
        };
    }

    protected function getMedicalTotal(array $payload)
    {
        $serviceIds = collect($payload['booking_data']['homecare']['services'])
            ->pluck('service_id')
            ->toArray();

        $services = $this->serviceRepository->findByFields([
            ['service_id', 'IN', $serviceIds],
        ]);

        return $services->sum('price');
    }

    protected function getAdlTotal(array $payload, string $branchId)
    {

        $contract = $this->branchContractRepository->findByField([
            ['branch_id', '=', $branchId],
            ['accommodation_type', '=', strtoupper($payload['booking_data']['homecare']['type'])],
            ['is_active', '=', true],
        ]);

        if (!$contract) {
            throw new Exception('No active ADL pricing contract is configured for this branch.', 404);
        }

        $total = $payload['booking_data']['homecare']['time_span'] * $contract->price;

        if ($total <= 0) {
            throw new Exception('The ADL pricing contract has an invalid price.', 422);
        }

        return $total;
    }

    protected function getFacilityTotal(array $payload, string $branchId)
    {

        $contract = $this->branchContractRepository->findByField([
            ['branch_id', '=', $branchId],
            ['accommodation_type', '=', strtoupper($payload['booking_data']['facility']['plan'])],
            ['billing_cycle', '=', strtoupper($payload['booking_data']['facility']['billing_cycle'])],
            ['is_active', '=', true],
        ]);

        if (!$contract) {
            throw new Exception(
                sprintf(
                    'No active %s (%s) pricing contract is configured for this branch.',
                    $payload['booking_data']['facility']['plan'],
                    $payload['booking_data']['facility']['billing_cycle']
                ),
                404
            );
        }

        if ($contract->price <= 0) {
            throw new Exception(
                sprintf(
                    'The %s (%s) pricing contract has an invalid price.',
                    $contract->accommodation_type,
                    $contract->billing_cycle
                ),
                422
            );
        }

        return $contract->price;
    }

    public function resolvePayment(array $payload)
    {
        $isFreePreAdmission = ($payload['category'] ?? null) === 'facility'
            && ($payload['booking_data']['facility']['type'] ?? null) === 'Pre-Admission';

        if ($isFreePreAdmission) {
            return ['total_amount' => 0, 'paid' => false];
        }

        return [
            'total_amount' => $this->getTotal($payload) ?? 0,
            'payment_status' => 'pending'
        ];
    }

    public function resolvePayment2(array $payload)
    {
        $contract = $this->branchContractRepository->findByField([
            ['branch_id', '=', $payload['branch_id']],
            ['branch_contract_id', '=', $payload['reserved']['contract_id']],
            ['is_active', '=', true],
        ]);

        if (!$contract) {
            throw new Exception(
                sprintf(
                    'No active %s (%s) pricing contract is configured for this branch.',
                    $payload['facility']['plan'] ??  $payload['reserved']['accommodation_plan'],
                    $payload['facility']['billing_cycle'] ?? $payload['reserved']['billing_cycle']
                ),
                404
            );
        }

        if ($contract->price <= 0) {
            throw new Exception(
                sprintf(
                    'The %s (%s) pricing contract has an invalid price.',
                    $contract->accommodation_type,
                    $contract->billing_cycle
                ),
                422
            );
        }

        return [
            'total_amount' => $contract->price ?? 0,
            'paid' => false,
        ];
    }

    public function resolveAssessment(array $assessment)
    {
        return $assessment;
    }

    public function resolveDiagnoses(mixed $diagnoses): array
    {
        if (!is_array($diagnoses)) {
            return [];
        }

        return array_values(array_map(
            fn($diagnosis) => $this->resolveDiagnosis((array) $diagnosis),
            $diagnoses
        ));
    }

    public function resolveDiagnosis(array $diagnosis)
    {
        if (empty($diagnosis['diagnosis_file'])) {
            return $diagnosis;
        }

        try {
            $uploadResult = SupabaseService::store($diagnosis['diagnosis_file']);

            if (empty($uploadResult['url'])) {
                throw new Exception('Diagnosis file upload failed: no URL returned.');
            }

            $diagnosis['diagnosis_file'] = $uploadResult['url'];
        } catch (\Throwable $e) {
            throw new Exception('We couldn\'t upload your diagnosis file. Please try again or use a different file.', 422, $e);
        }

        return $diagnosis;
    }
}
