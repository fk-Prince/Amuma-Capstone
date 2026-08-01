<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Repository\RoomRepository;
use App\Http\Resources\RoomResource;
use App\Models\User;
use App\Repository\BranchRepository;

use Exception;

class RoomService
{
    private RoomRepository $roomRepository;

    public function __construct(RoomRepository $roomRepository)
    {
        $this->roomRepository = $roomRepository;
    }

    public function overview(User $user, array $payload)
    {
        return $this->roomRepository->getRoomStats($payload['branch_id']);
    }


    public function createRoom(User $user, array $payload)
    {
        $existingRoom = $this->roomRepository->findByField([
            ['branch_id', '=', $payload['branch_id']],
            ['room_no', '=', $payload['room_no']],
        ]);

        if ($existingRoom) {
            throw new Exception(__('Room number already exists in this branch.'), 409);
        }

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
        $model = $this->roomRepository->paginate($payload['branch_id'], $payload, $payload['per_page']);
        return RoomResource::collection($model);
    }

    public function updateRoom(User $user, string $id, array $payload)
    {
        $existingRoom = $this->roomRepository->findByField([
            ['branch_id', '=', $payload['branch_id']],
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
