<?php

namespace App\Service\Booking;

use App\Repository\BranchContractRepository;
use App\Repository\ServiceRepository;
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
            'homecare' => match ($payload['booking_data']['service']['type']) {
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
        $serviceIds = collect($payload['booking_data']['service']['services'])
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
            ['accommodation_type', '=', strtoupper($payload['booking_data']['service']['type'])],
            ['is_active', '=', true],
        ]);

        if (!$contract) {
            throw new Exception('No active ADL pricing contract is configured for this branch.', 404);
        }

        $total = $payload['booking_data']['service']['time_span'] * $contract->price;

        if ($total <= 0) {
            throw new Exception('The ADL pricing contract has an invalid price.', 422);
        }

        return $total;
    }

    protected function getFacilityTotal(array $payload, string $branchId)
    {

        $contract = $this->branchContractRepository->findByField([
            ['branch_id', '=', $branchId],
            ['accommodation_type', '=', strtoupper($payload['booking_data']['service']['plan'])],
            ['billing_cycle', '=', strtoupper($payload['booking_data']['service']['billing_cycle'])],
            ['is_active', '=', true],
        ]);

        if (!$contract) {
            throw new Exception(
                sprintf(
                    'No active %s (%s) pricing contract is configured for this branch.',
                    $payload['booking_data']['service']['plan'],
                    $payload['booking_data']['service']['billing_cycle']
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
}
