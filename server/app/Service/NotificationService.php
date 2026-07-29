<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Events\NotificationEvent;
use App\Guard\BranchGuard;
use App\Repository\NotificationRepository;
use App\Http\Resources\NotificationResource;
use App\Models\User;
use App\Repository\BranchRepository;
use App\Repository\ModuleRepository;
use Exception;

class NotificationService
{
    private NotificationRepository $notificationRepository;
    private  BranchRepository $branchRepository;
    private  ModuleRepository $moduleRepository;

    public function __construct(NotificationRepository $notificationRepository, BranchRepository $branchRepository, ModuleRepository $moduleRepository)
    {
        $this->notificationRepository = $notificationRepository;
        $this->branchRepository = $branchRepository;
        $this->moduleRepository = $moduleRepository;
    }

    public function sendNotification(array $payload)
    {

        $branch = BranchGuard::resolveBranch($this->branchRepository, $payload['branch_uuid']);

        $employees = $this->moduleRepository->getEmployeesModuleWithPermission(
            [PermissionAction::Read],
            ModuleEnum::Bookings,
            $branch->branch_id
        );

        foreach ($employees as $employee) {
            $this->notificationRepository->create([
                'branch_id' => $payload['branch_id'],
                'to_user_id' => $employee['user_id'],
                'from_user_id' => $payload['user_id'],
                'message_type' => 'Booking',
                'message' => $payload['message'],
            ]);

            event(new NotificationEvent(
                $employee['uuid'],
                $payload['message'],
                $payload['reference_id'],
                $branch->uuid
            ));
        }

        return ['message' => 'Successfully Send Notification'];
    }

    public function listNotification(array $payload, User $user)
    {
        $branch = BranchGuard::resolveBranch($this->branchRepository, $payload['branch_uuid']);
        $collection = $this->notificationRepository->paginate($payload['per_page'], $user->user_id, $branch->branch_id);
        return NotificationResource::collection($collection);
    }
}
