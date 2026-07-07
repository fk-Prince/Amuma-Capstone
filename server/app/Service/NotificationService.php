<?php

namespace App\Service;

use App\Repository\NotificationRepository;
use App\Http\Resources\NotificationResource;
use App\Models\User;
use App\Repository\BranchRepository;
use Exception;

class NotificationService
{
    private NotificationRepository $notificationRepository;
    private  BranchRepository $branchRepository;

    public function __construct(NotificationRepository $notificationRepository, BranchRepository $branchRepository)
    {
        $this->notificationRepository = $notificationRepository;
        $this->branchRepository = $branchRepository;
    }

    // public function createNotification(User $actor, array $payload)
    // {
    //     $model = $this->notificationRepository->create($payload);
    //     return new NotificationResource($model);
    // }

    public function listNotification(array $payload, User $user)
    {
        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);
        if (!$branch) {
            throw new Exception("Branch not found");
        }
        $collection = $this->notificationRepository->paginate($payload['per_page'], $user->user_id, $branch->branch_id);
        return NotificationResource::collection($collection);
    }
}
