<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
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

        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

        if (!$branch) {
            throw new Exception(__('Branch does not exist.'), 404);
        }
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::RoomsAndBeds, PermissionAction::Create);

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
        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

        if (!$branch) {
            throw new Exception(__('Branch does not exist.'), 404);
        }

        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::RoomsAndBeds, PermissionAction::Read);

        $model = $this->roomRepository->paginate($payload['per_page'], $branch->branch_id);

        return  new RoomResource($model);
    }

    public function updateRoom(User $user, string $id, array $payload)
    {


        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

        if (!$branch) {
            throw new Exception(__('Branch does not exist.'), 404);
        }

        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::RoomsAndBeds, PermissionAction::Update);

        if (! $branch->hasFacilitySubscription()) {
            throw new Exception(__('No active facility subscription.'), 403);
        }

        $existingRoom = $this->roomRepository->findByField([
            ['branch_id', '=', $branch->branch_id],
            ['room_id', '=',  $id],
        ]);

        $existingRoom->update([
            'room_no'   => $payload['room_no'],
            'floor'     => $payload['floor'],
            'capacity'  => $payload['capacity'],
            'room_type' => $payload['room_type'],
            'status'    => $payload['status'],
        ]);

        return response()->json([
            'data' => $existingRoom->fresh(),
            'message' => __('Successfully updated a room.'),
        ], 201);
    }
}
