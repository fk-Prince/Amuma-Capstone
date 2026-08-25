<?php

namespace App\Service;

use App\Events\NotificationEvent;
use App\Guard\BranchGuard;
use App\Http\Resources\NotificationResource;
use App\Models\Branch;
use App\Models\User;
use App\Repository\EmployeeRepository;
use App\Repository\NotificationRepository;

class NotificationService
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private EmployeeRepository $employeeRepository,
    ) {}

    private const BOOKING_ROLES = ['admission'];

    public function sendNotification(array $payload, object $booking)
    {

        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        $employees = $this->employeeRepository->getBranchStaffByRoles(
            self::BOOKING_ROLES,
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
        $branchId = null;

        if (! empty($payload['branch_uuid'])) {
            $branchId = BranchGuard::resolveBranch($payload['branch_uuid'])->branch_id;
        }

        $collection = $this->notificationRepository->paginate(
            (int) ($payload['per_page'] ?? 15),
            $user->user_id,
            $branchId,
            $payload['unread_only'] ?? false
        );

        return NotificationResource::collection($collection)
            ->additional([
                'meta' => [
                    'unread_count' => $this->notificationRepository
                        ->unreadCount($user->user_id, $branchId),
                ],
            ]);
    }

    public function markRead(array $payload, User $user)
    {
        $updated = $this->notificationRepository->markRead(
            $user->user_id,
            $payload['notification_id'] ?? null
        );

        return response()->json([
            'status' => true,
            'message' => __('Notification updated.'),
            'updated' => $updated,
            'unread_count' => $this->notificationRepository
                ->unreadCount($user->user_id, null),
        ]);
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
