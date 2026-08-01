<?php

namespace App\Service;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;
use App\Repository\BookingRepository;
use App\Repository\InvoiceRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class InvoiceService
{


    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private BookingRepository $bookingRepository,
    ) {}


    public function createInvoiceService(array $services, string $branchId)
    {
        $invoice = $this->invoiceRepository->create([
            'status' => Invoice::STATUS_PENDING,
            'total' => 0,
            'branch_id' => $branchId
        ]);

        $total = 0;
        foreach ($services as $service) {
            $invoice->invoiceServices()->create([
                'price' => $service['price'],
                'schedule_services_id' => $service['schedule_services_id'],
            ]);
            $total += $service['price'];
        }
        $invoice->update([
            'total' => $total
        ]);
        return $invoice;
    }

    public function storeBooking(User $user, array $payload)
    {
        return DB::transaction(function () use ($user, $payload) {

            $booking = $this->bookingRepository->findByField([
                ['reference_id', '=', $payload['reference_id']]
            ]);

            if (!$booking) {
                throw new Exception("Booking not found", 404);
            }

            $nonProcessableStatuses = [
                Booking::STATUS_PENDING,
                Booking::STATUS_REJECTED,
                Booking::STATUS_APPROVED,
                Booking::STATUS_EXPIRED,
                Booking::STATUS_COMPLETED
            ];

            if (in_array($booking->status, $nonProcessableStatuses)) {
                throw new Exception(
                    "Booking cannot be processed because its current status is '{$booking->status}'.",
                    400
                );
            }

            if (strtolower($booking['category']) == "facility") {
                $bookingData = $booking['booking_data'];
                $admitted_at =
                    $booking['booking_data']['service']['admitted_date'] ??
                    $bookingData['payment']['admitted_at'] ??
                    $payload['admitted_date']  ??
                    null;

                if ($payload['cash'] < $bookingData['payment']['total_amount']) {
                    throw new Exception("Insufficient payment. Required amount: {$bookingData['payment']['total_amount']}, received: {$payload['cash']}", 400);
                }



                $bookingData['payment']['paid'] = true;
                $bookingData['reserved']['admitted_at'] = $admitted_at;
                $booking->update([
                    'booking_data' => $bookingData,
                    'status' => Booking::STATUS_AWAITING,
                ]);
                // $patient->bookings()->create([
                //     'booking_id' => $booking['booking_id']
                // ]);
                return [
                    // 'invoice' => $invoice,
                    // 'patient' => $patient,
                    // 'patientAccess' => $patientData['patientAccess'],
                    // 'admission' => $admission,
                    // 'message' => 'Facility booking has been processed successfully. The patient has been admitted and the invoice has been generated.',
                    'message' => 'Facility booking has been processed successfully. The invoice has been generated, and the patient may now proceed with admission.',
                ];
            }
            throw new Exception("Unsupported booking category.", 400);
        });
    }

    public function retreiveBooking(User $user, array $payload, string $id)
    {
        if (!$id) return null;
        if (str_starts_with(strtoupper($id), 'BKN')) {
            $booking = $this->bookingRepository->findByField([
                ['reference_id', '=', strtoupper($id)],
                ['branch_id', '=', $payload['branch_id']]
            ]);

            return response()->json([
                'data' => $booking,
                'total' => $booking['booking_data']['payment']['total_amount']
            ], 200);
        } else if (str_starts_with(strtoupper($id), 'SCH')) {
            $schedule = $this->invoiceRepository->getInvoiceBalanceForSchedule($payload['id']);
            return $schedule;
        }
    }
}
