<?php

namespace App\Service;

use App\Models\Booking;
use App\Models\User;
use App\Repository\BookingRepository;
use App\Repository\LocationRepository;
use App\Repository\PatientRepository;
use App\Repository\UserRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class AdmissionService
{

    public function __construct(
        private BookingRepository $bookingRepository,
    ) {}

    public function storeAdmission(array $payload)
    {
        Log::info($payload);
        $referenceId = $payload['reference_id'] ?? null;
        if ($referenceId) {
            $booking = $this->bookingRepository->findByField([
                ['reference_id', '=', $referenceId]
            ]);

            if ($booking) {
                $booking->update([
                    'booking_data' => $payload['booking_data'],
                    'status' => Booking::STATUS_AWAITING,
                ]);

                return response()->json([
                    'message' => 'Admission is updated successfully.',
                    'booking' => $booking,
                ], 201);
            } else {
                throw new Exception('Booking reference not found.', 404);
            }
        }

        $bookingPayload = [
            'branch_id' => $payload['branch_id'],
            'booking_data' => $payload['booking_data'],
            'status' => Booking::STATUS_AWAITING,
            'category' => Booking::CATEGORY_FACILITY,
            'valid_until' => Carbon::now()->addDay(),
        ];

        $booking = $this->bookingRepository->create($bookingPayload);

        return response()->json([
            'message' => 'Your admission has been successfully created and is now awaiting payment.',
            'booking' => $booking,
        ], 201);
    }
}
