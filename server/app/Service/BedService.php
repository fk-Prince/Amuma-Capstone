<?php

namespace App\Service;

use App\Repository\BedRepository;
use App\Http\Resources\BedResource;
use App\Models\User;
use App\Repository\BranchRepository;
use App\Repository\RoomRepository;
use App\Service\Utils\AuthGuard;
use Exception;
use Illuminate\Support\Facades\DB;

class BedService
{
    private BedRepository $bedRepository;
    private BranchRepository $branchRepository;
    private  RoomRepository $roomRepository;

    public function __construct(BedRepository $bedRepository, BranchRepository $branchRepository, RoomRepository $roomRepository)
    {
        $this->bedRepository = $bedRepository;
        $this->branchRepository = $branchRepository;
        $this->roomRepository = $roomRepository;
    }

    public function createBed(User $user, array $payload)
    {
        AuthGuard::requireRole($user, ['branch_owner', 'administrator']);

        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

        if (! $branch) {
            return response()->json([
                'status' => false,
                'message' => __('Branch does not exist.')
            ], 404);
        }

        if (!$branch->hasFacilitySubscription()) {
            return response()->json([
                'status' => false,
                'message' => __('No active facility subscription.')
            ], 403);
        }

        $existingRoom = $this->roomRepository->findByField([
            ['branch_id', '=', $branch->branch_id],
            ['room_no', '=', $payload['room_no']],
        ]);

        if (!$existingRoom) {
            throw new Exception(__('Room doenst exists in this branch.'), 409);
        }

        $currentBedsCount = $existingRoom->beds()->count();
        $availableSlots = $existingRoom->capacity - $currentBedsCount;

        if ($availableSlots <= 0) {
            throw new Exception(__("Room capacity exceeded. Cannot add these beds."), 422);
        }

        $createdBeds = $this->bedRepository->create([
            'room_id' => $existingRoom->room_id,
            'bed_no' => $payload['bed_no'],
            'status' => 'available',
        ]);

        return response()->json([
            'message' => __('Beds created successfully.'),
            'data' => $createdBeds,
        ], 201);
    }
}
