<?php

namespace App\Service;

use App\Repository\RoomRepository;
use App\Http\Resources\RoomResource;
use App\Models\User;
use App\Repository\BranchRepository;
use App\Service\Utils\AuthGuard;
use Exception;

class RoomService
{
    private RoomRepository $roomRepository;
    private BranchRepository $branchRepository;

    public function __construct(RoomRepository $roomRepository, BranchRepository $branchRepository)
    {
        $this->branchRepository = $branchRepository;
        $this->roomRepository = $roomRepository;
    }


    public function createRoom(User $user, array $payload)
    {
        AuthGuard::requireRole($user, ['branch_owner', 'administrator']);

        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

        if (!$branch) {
            throw new Exception(__('Branch does not exist.'), 404);
        }

        if (! $branch->hasFacilitySubscription()) {
            throw new Exception(__('No active facility subscription.'), 403);
        }

        $existingRoom = $this->roomRepository->findByField([
            ['branch_id', '=', $branch->branch_id],
            ['room_no', '=', $payload['room_no']],
        ]);


        if ($existingRoom) {
            throw new Exception(__('Room number already exists in this branch.'), 409);
        }

        $payload['branch_id'] = $branch->branch_id;

        $model = $this->roomRepository->create($payload);

        if (! $model) {
            throw new Exception(__('Failed to create room.'), 500);
        }

        return response()->json([
            'data' => new RoomResource($model),
            'message' => __('Successfully created a room.'),
            'status' => true,
        ], 201);
    }

    public function listRoom(User $user, array $payload)
    {
        AuthGuard::requireUser($user);
        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

        if (!$branch) {
            throw new Exception(__('Branch does not exist.'), 404);
        }

        $model = $this->roomRepository->paginate($payload['per_page'], $branch->branch_id);

        return  new RoomResource($model);
    }
}
