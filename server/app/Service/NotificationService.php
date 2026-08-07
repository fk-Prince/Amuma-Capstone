<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Events\NotificationEvent;
use App\Guard\BranchGuard;
use App\Repository\NotificationRepository;
use App\Http\Resources\NotificationResource;
use App\Models\Branch;
use App\Models\User;
use App\Repository\BranchRepository;
use App\Repository\ModuleRepository;
use Exception;

class NotificationService
{
    private NotificationRepository $notificationRepository;
    private  ModuleRepository $moduleRepository;

    public function __construct(NotificationRepository $notificationRepository,  ModuleRepository $moduleRepository)
    {
        $this->notificationRepository = $notificationRepository;
        $this->moduleRepository = $moduleRepository;
    }

    public function sendNotification(array $payload, object $booking)
    {

        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        $employees = $this->moduleRepository->getEmployeesModuleWithPermission([PermissionAction::Read], ModuleEnum::Bookings, $branch->branch_id);

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
                $branch->uuid,
                $payload['message'],
                $payload['reference_id'],
                'Booking',
                $booking
            ));
        }

        return ['message' => 'Successfully Send Notification'];
    }

    public function listNotification(array $payload, User $user)
    {
        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        $collection = $this->notificationRepository->paginate($payload['per_page'], $user->user_id, $branch->branch_id);
        return NotificationResource::collection($collection);
    }

    public function notifyNewBooking(Branch $branch, User $user, object $booking): void
    {
        $this->sendNotification([
            'branch_id'    => $branch->branch_id,
            'branch_uuid'  => $branch->uuid,
            'user_id'      => $user->user_id,
            'reference_id' => $booking->booking_id,
            'message'      => "You have a new booking request. Booking #{$booking->reference_id} is waiting for your review.",
        ], $booking);
    }
}
