<?php

namespace App\Service;

use App\Events\NotificationEvent;
use App\Guard\BranchGuard;
use App\Http\Resources\NotificationResource;
use App\Mail\BookingDecisionMailer;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\User;
use App\Repository\EmployeeRepository;
use App\Repository\NotificationRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private EmployeeRepository $employeeRepository,
    ) {}


    private const BOOKING_ROLES = ['admission', 'branch_owner', 'administrator'];

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


    public function notifyBookingDecision(
        Branch $branch,
        object $booking,
        string $decision,
        ?User $actor = null,
        ?string $reason = null
    ): void {
        $data = is_string($booking->booking_data)
            ? json_decode($booking->booking_data, true)
            : ($booking->booking_data ?? []);

        $client = $booking->user_id ? User::find($booking->user_id) : null;

        $verb = $decision === Booking::STATUS_APPROVED ? 'approved' : 'rejected';

        $reason = $reason ?? ($booking->reason ?? null);
        $reason = trim((string) $reason) ?: null;

        if ($verb === 'approved') {
            $message = "Your booking {$booking->reference_id} has been approved by {$branch->name}.";
        } else {
            $message = "Your booking {$booking->reference_id} was declined by {$branch->name}.";

            if ($reason) {
                $message .= " Reason: {$reason}";
            }
        }

        if ($client) {
            $this->notificationRepository->create([
                'branch_id' => $branch->branch_id,
                'to_user_id' => $client->user_id,
                'from_user_id' => $actor?->user_id ?? $client->user_id,
                'message_type' => 'Booking',
                'message' => $message,
            ]);

            if ($client->uuid) {
                event(new NotificationEvent(
                    $client->uuid,
                    $branch->uuid,
                    $message,
                    (string) $booking->reference_id,
                    'Booking',
                    $booking
                ));
            }
        }

        $email = $client?->email ?? ($data['guardian']['email'] ?? null);

        if (! $email) {
            return;
        }

        $recipientName = trim(implode(' ', array_filter([
            $data['guardian']['first_name'] ?? null,
            $data['guardian']['last_name'] ?? null,
        ]))) ?: ($client?->first_name ?? 'there');

        $patientName = trim(implode(' ', array_filter([
            $data['patient']['first_name'] ?? null,
            $data['patient']['last_name'] ?? null,
        ]))) ?: null;

        try {
            Mail::to($email)->send(new BookingDecisionMailer(
                (string) $booking->reference_id,
                $verb,
                $recipientName,
                (string) $branch->name,
                $patientName,
                $reason
            ));
        } catch (\Throwable $e) {
            Log::error('Booking decision email failed: ' . $e->getMessage(), [
                'reference_id' => $booking->reference_id,
                'email' => $email,
            ]);
        }
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
