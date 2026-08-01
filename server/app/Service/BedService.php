<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Repository\BedRepository;
use App\Http\Resources\BedResource;
use App\Models\User;
use App\Repository\BranchRepository;
use App\Repository\RoomRepository;
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


        $room = $this->roomRepository->findByField([
            ['room_id', '=', $payload['room_id']],
            ['branch_id', '=', $payload['branch_id']],
        ]);

        if (! $room) {
            throw new Exception(__('Room does not exist in this branch.'), 404);
        }

        $existingBed = $this->bedRepository->findByField([
            ['room_id', '=', $room->room_id],
            ['bed_no', '=', $payload['bed_no']],
        ]);

        if ($existingBed) {
            throw new Exception(__('Bed name already exists in this room.'), 409);
        }

        $currentBedsCount = $room->beds()->count();

        if ($currentBedsCount >= $room->capacity) {
            throw new Exception(__('Room capacity exceeded. Cannot add more beds.'), 422);
        }

        $createdBed = DB::transaction(function () use ($room, $payload) {
            return  $this->bedRepository->create([
                'room_id' => $room->room_id,
                'bed_no' => $payload['bed_no'],
                'status' => $payload['status'] ?? 'Available',
            ]);
        });

        return response()->json([
            'message' => __('Bed created successfully.'),
            'data' => $createdBed,
        ], 201);
    }


    public function updateBed(User $user, array $payload, string $bedId)
    {

        $room = $this->roomRepository->findByField([
            ['room_id', '=', $payload['room_id']],
            ['branch_id', '=', $payload['branch_id']],
        ]);

        if (!$room) {
            throw new Exception(__('Room does not exist in this branch.'), 404);
        }

        $bed = $this->bedRepository->findByField([
            ['bed_id', '=', $bedId],
            ['room_id', '=', $room->room_id],
        ]);

        if (!$bed) {
            throw new Exception(__('Bed does not exist.'), 404);
        }

        $existingBed = $this->bedRepository->findByField([
            ['room_id', '=', $room->room_id],
            ['bed_no', '=', $payload['bed_no']],
            ['bed_id', '!=', $bed->bed_id],
        ]);

        if ($existingBed) {
            throw new Exception(__('Bed name already exists in this room.'), 409);
        }

        DB::transaction(function () use ($bed, $room, $payload) {
            $bed->update([
                'room_id' => $room->room_id,
                'bed_no' => $payload['bed_no'],
                'status' => $payload['status'] ?? $bed->status,
            ]);
        });

        return response()->json([
            'message' => __('Bed updated successfully.'),
            'data' => $bed->fresh(),
        ], 200);
    }

    public function findAvailableBed(int $bedId)
    {
        $bed = $this->bedRepository->findByField([
            ['bed_id', '=', $bedId],
        ]);

        if (!$bed) {
            throw new Exception('Bed not found.', 404);
        }

        if ($bed->status === 'Occupied') {
            throw new Exception('Selected bed is already occupied.', 422);
        }

        return $bed;
    }
}
