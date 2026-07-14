<?php

namespace App\Service;

use App\Repository\RoomTransferRepository;
use App\Http\Resources\RoomTransferResource;
use App\Models\User;

class RoomTransferService
{
    private RoomTransferRepository $roomTransferRepository;

    public function __construct(RoomTransferRepository $roomTransferRepository)
    {
        $this->roomTransferRepository = $roomTransferRepository;
    }

    public function createRoomTransfer(User $actor, array $payload)
    {
        if (! $actor->hasRole('superadmin')) {
            $payload['company_id'] = $actor->company_id;
        }

        $model = $this->roomTransferRepository->create($payload);
        return new RoomTransferResource($model);
    }

    public function listRoomTransfer(User $actor, int $perPage = 15)
    {
        $companyId = $actor->hasRole('superadmin') ? null : $actor->company_id;

        $collection = $this->roomTransferRepository->paginate($perPage, $companyId);
        return RoomTransferResource::collection($collection);
    }

    /**
     * Helper to ensure the actor owns the record
     */
    private function findScoped(User $actor, string $uuid)
    {
        $model = $this->roomTransferRepository->findByUuid($uuid);

        if (! $model) {
            abort(404, 'Resource not found');
        }

        if (! $actor->hasRole('superadmin')) {
            if ($model->company_id !== $actor->company_id) {
                abort(403, 'Unauthorized access to this resource.');
            }
        }
        return $model;
    }

    public function getRoomTransfer(User $actor, string $uuid)
    {
        $model = $this->findScoped($actor, $uuid);
        return new RoomTransferResource($model);
    }

    public function updateRoomTransfer(User $actor, string $uuid, array $payload)
    {
        $this->findScoped($actor, $uuid);

        unset($payload['company_id']);

        $model = $this->roomTransferRepository->update($uuid, $payload);
        return new RoomTransferResource($model);
    }

    public function deleteRoomTransfer(User $actor, string $uuid)
    {
        $this->findScoped($actor, $uuid);
        $this->roomTransferRepository->delete($uuid);
        return true;
    }

    public function restoreRoomTransfer(User $actor, string $uuid)
    {
        $model = $this->roomTransferRepository->restore($uuid);

        if (! $actor->hasRole('superadmin') && $model->company_id !== $actor->company_id) {
            $model->delete();
            abort(403, 'Unauthorized');
        }

        return new RoomTransferResource($model);
    }
}
