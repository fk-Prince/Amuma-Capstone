<?php

namespace App\Service;


use App\Repository\BranchContractRepository;
use App\Http\Resources\BranchContractResource;
use App\Models\Bed;
use App\Models\Booking;
use App\Models\BranchContract;
use App\Models\User;
use App\Repository\BookingRepository;
use App\Repository\RoomRepository;
use Exception;

class BranchContractService
{
    public function __construct(
        private BranchContractRepository $branchContractRepository,
        private RoomRepository $roomRepository,
    ) {}


    public function overview(array $payload)
    {
        return [
            'total_active_plans' => $this->branchContractRepository->overview($payload, $payload['branch_id']),
            'patient_with_plan' => 5,
            'new_monthy_patients' => 10,
            'patient_retention' => '99%',
            "active_patient" =>  "0",
            "caregivers" =>  "0",
            "scheduled_visits" =>  "0",
            "homecare_retention" =>  "0",
        ];
    }

    public function createBranchContract(array $payload)
    {
        $existingContract = $this->branchContractRepository->findByField([
            ['branch_id', '=', $payload['branch_id']],
            ['category', '=', $payload['category']],
            ['accommodation_type', '=', $payload['accommodation_type']],
            ['billing_cycle', '=', $payload['billing_cycle']],
        ]);

        if ($existingContract) {
            throw new Exception(
                "A {$payload['category']} {$payload['accommodation_type']} {$payload['billing_cycle']} contract already exists for this branch.",
                409
            );
        }

        $payload = [
            'branch_id' => $payload['branch_id'],
            'category' => $payload['category'],
            'accommodation_type' => $payload['accommodation_type'],
            'price' => $payload['price'],
            'billing_cycle' => $payload['billing_cycle'],
            'description' => $payload['description'] ?? null,
        ];

        $contract = $this->branchContractRepository->create($payload);

        return [
            'message' => 'Branch contract created successfully.',
            'data' => $contract,
        ];
    }

    public function list(array $payload)
    {
        $data = $this->branchContractRepository->all($payload['branch_id']);
        return $data;
    }

    public function updateBranchContract(array $payload, string $id)
    {
        $contract = $this->branchContractRepository->findByField([
            ['branch_contract_id', '=', $id],
        ]);

        if (!$contract) {
            throw new Exception("Branch contract doesn't exist", 404);
        }

        $existingContract = $this->branchContractRepository->findByField([
            ['branch_id', '=', $payload['branch_id']],
            ['category', '=', $payload['category']],
            ['accommodation_type', '=', $payload['accommodation_type']],
            ['billing_cycle', '=', $payload['billing_cycle']],
            ['branch_contract_id', '!=', $payload['branch_contract_id']],
        ]);


        if ($existingContract) {
            throw new Exception(
                "A {$payload['category']} {$payload['accommodation_type']} {$payload['billing_cycle']} contract already exists for this branch.",
                409
            );
        }
        $contract->update([
            'category' => $payload['category'],
            'accommodation_type' => $payload['accommodation_type'],
            'price' => $payload['price'],
            'billing_cycle' => $payload['billing_cycle'],
            'description' => $payload['description'] ?? null,
        ]);

        return [
            'message' => 'Branch contract updated successfully.',
            'data' => $contract->fresh(),
        ];
    }

    public function roomContract(array $payload)
    {
        $contracts = $this->branchContractRepository->findAllByConditions([
            ['category', '=', BranchContract::CAREGORY_FACILITY],
            ['branch_id', '=', $payload['branch_id']],
            ['is_active', '=', true],
        ]);

        $rooms = $this->roomRepository->findAllByConditions([
            ['branch_id', '=', $payload['branch_id']],
        ]);
        $rooms->load('availableBeds');

        return $contracts->map(function ($contract) use ($rooms) {

            $matchingRooms = $rooms
                ->filter(
                    fn($room) =>
                    strcasecmp($room->room_type, $contract->accommodation_type) === 0
                )
                ->map(function ($room) {

                    $beds = $room->availableBeds
                        ->map(fn($bed) => [
                            'bed_id' => $bed->bed_id,
                            'bed_no' => $bed->bed_no,
                            'status' => $bed->status,
                        ])
                        ->values();

                    $availableBeds = $beds
                        ->filter(fn($bed) => $bed['status'] === Bed::STATUS_AVAILABLE)
                        ->values();

                    $reservedBeds = $beds
                        ->filter(fn($bed) => $bed['status'] === Bed::STATUS_RESERVED)
                        ->values();

                    return [
                        'capacity' => $room->capacity,
                        'room_id' => $room->room_id,
                        'room_no' => $room->room_no,
                        'room_type' => $room->room_type,
                        'floor' => $room->floor,
                        'available_beds_count' => $availableBeds->count(),
                        'reserved_beds_count' => $reservedBeds->count(),
                        'beds' => $beds,
                    ];
                })
                ->values();

            if ($matchingRooms->isEmpty()) {
                return null;
            }

            $totalReservedBeds = $matchingRooms->sum('reserved_beds_count');

            return [
                'contract_id' => $contract->branch_contract_id,
                'accommodation_type' => $contract->accommodation_type,
                'price' => $contract->price,
                'billing_cycle' => $contract->billing_cycle,
                'total_reserved_beds' => $totalReservedBeds,
                'available_beds_count' => $matchingRooms->sum('available_beds_count'),
                'reserved_beds_count' => $matchingRooms->sum('reserved_beds_count'),
                'rooms' => $matchingRooms,
            ];
        })
            ->filter()
            ->values();
    }

    public function show(array $payload)
    {
        $contractId = $payload['contract_id'] ?? $payload['branch_contract_id'];
        return $this->branchContractRepository->findByField([
            ['branch_contract_id', '=', $contractId]
        ]);
    }
}
