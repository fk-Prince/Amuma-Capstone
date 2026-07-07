<?php

namespace App\Service;

use App\Events\NotificationEvent;
use App\Repository\BookingRepository;
use App\Http\Resources\BookingResource;
use App\Models\User;
use App\Repository\BranchRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Stevebauman\Location\Facades\Location;

class BookingService
{
    private BookingRepository $bookingRepository;
    private  NotificationRepository $notificationRepository;
    private  BranchRepository $branchRepository;
    private UserRepository $userRepository;

    public function __construct(BookingRepository $bookingRepository, BranchRepository $branchRepository, NotificationRepository $notificationRepository, UserRepository $userRepository)
    {
        $this->bookingRepository = $bookingRepository;
        $this->branchRepository = $branchRepository;
        $this->notificationRepository = $notificationRepository;
        $this->userRepository = $userRepository;
    }



    public function createBooking(User $user, array $payload)
    {
        return DB::transaction(function () use ($user, $payload) {

            $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

            if (!$branch) {
                throw new Exception("Branch doesnt exist");
            }

            $bookingData = [
                'user_id' => $user->user_id,
                'branch_id' => $branch->branch_id,
                'category' => ucfirst($payload['category']),
                'booking_data' => $payload['booking_data'],
                'valid_until' => Carbon::now()->addDay(),
            ];

            $booking = $this->bookingRepository->create($bookingData);

            if (!$booking) {
                throw new Exception("Failed to create booking.");
            }

            $message = "You have a new booking request. Booking #{$booking->reference_id} is waiting for your review.";

            $users = $this->userRepository->getAllUsersByBranchAndRole($branch->branch_id,  'branch_owner');

            foreach ($users as $branchUser) {
                $this->notificationRepository->create([
                    'branch_id' => $branch->branch_id,
                    'to_user_id' => $branchUser->user_id,
                    'from_user_id' => $user->user_id,
                    'message_type' => 'Booking',
                    'message' => $message,
                ]);

                event(new NotificationEvent(
                    $branchUser->uuid,
                    $message,
                    $booking->reference_id,
                    $branch->uuid
                ));
            }



            return $booking;
        });
    }
}
