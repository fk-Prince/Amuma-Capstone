<?php

namespace App\Service;

use App\Http\Resources\InvoiceResource;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;
use App\Repository\BookingRepository;
use App\Repository\InvoiceRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{


    public function __construct(
        private InvoiceRepository $invoiceRepository,
    ) {}

    public function overview(array $payload)
    {
        return $this->invoiceRepository->overview($payload);
    }
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
            $mode = $payload['mode'];
            if ($mode == 'schedule' || $mode == 'invoice') {
                $invoice = $this->invoiceRepository->findByField([
                    ['invoice_code', '=', $payload['invoice_code']]
                ]);

                if (!$invoice) {
                    throw new Exception("Invoice not found", 404);
                }

                $totalPaid = $invoice->payments()->sum('amount');
                $balance = $invoice->total - $totalPaid;

                if ($balance <= 0) {
                    throw new Exception("Invoice is already fully paid.", 400);
                }

                $cash = $payload['cash'];

                $change = max($cash - $balance, 0);

                $paymentAmount = min($cash, $balance);

                $invoice->payments()->create([
                    'amount' => $paymentAmount,
                    'payment_method' => $payload['payment_method'],
                ]);

                $totalPaid += $paymentAmount;

                $status = Invoice::STATUS_PENDING;

                if ($totalPaid >= $invoice->total) {
                    $status = Invoice::STATUS_PAID;
                } elseif ($totalPaid > 0) {
                    $status = Invoice::STATUS_PARTIAL;
                }

                $invoice->update([
                    'status' => $status,
                ]);

                return [
                    'message' => 'Invoice payment has been processed successfully.',
                    'change' => $change,
                ];
            } else if ($mode == 'patient') {
                $cash = $payload['cash'];
                $patientUuid = $payload['p_uuid'];

                $invoices = $this->invoiceRepository->getUnpaidInvoiceByPatient($patientUuid, $payload['branch_id']);

                foreach ($invoices as $invoice) {
                    if ($cash <= 0) {
                        break;
                    }
                    $paid = $invoice->payments()->sum('amount');
                    $balance = $invoice->total - $paid;
                    if ($balance <= 0) {
                        continue;
                    }
                    $paymentAmount = min($cash, $balance);
                    $invoice->payments()->create([
                        'amount' => $paymentAmount,
                        'payment_method' => $payload['payment_method'],
                    ]);
                    $cash -= $paymentAmount;
                    $paid += $paymentAmount;
                    if ($paid >= $invoice->total) {
                        $invoice->update([
                            'status' => 'paid',
                        ]);
                    } else {
                        $invoice->update([
                            'status' => 'partial',
                        ]);
                    }
                }

                //     else if ($mode == 'booking') {

                //     $booking = $this->bookingRepository->findByField([
                //         ['reference_id', '=', $payload['reference_id']]
                //     ]);

                //     if (!$booking) {
                //         throw new Exception("Booking not found", 404);
                //     }

                //     $nonProcessableStatuses = [
                //         Booking::STATUS_PENDING,
                //         Booking::STATUS_REJECTED,
                //         // Booking::STATUS_INPROGRESS,
                //         Booking::STATUS_EXPIRED,
                //         // Booking::STATUS_COMPLETED
                //     ];

                //     if (in_array($booking->status, $nonProcessableStatuses)) {
                //         throw new Exception(
                //             "Booking cannot be processed because its current status is '{$booking->status}'.",
                //             400
                //         );
                //     }

                //     if (strtolower($booking['category']) == "facility") {
                //         $bookingData = $booking['booking_data'];
                //         $admitted_at =
                //             $booking['booking_data']['service']['admitted_date'] ??
                //             $bookingData['payment']['admitted_at'] ??
                //             $payload['admitted_date']  ??
                //             null;

                //         if ($payload['cash'] < $bookingData['payment']['total_amount']) {
                //             throw new Exception("Insufficient payment. Required amount: {$bookingData['payment']['total_amount']}, received: {$payload['cash']}", 400);
                //         }
                //         $bookingData['payment']['paid'] = true;
                //         $bookingData['reserved']['admitted_at'] = $admitted_at;
                //         $booking->update([
                //             'booking_data' => $bookingData,
                //             // 'status' => Booking::STATUS_INPROGRESS,
                //         ]);
                //         // $patient->bookings()->create([
                //         //     'booking_id' => $booking['booking_id']
                //         // ]);
                //         return [
                //             'message' => 'Booking payment has been processed successfully. The booking is now ready for the next step.',
                //         ];
                //     }
                // } 

                return [
                    'message' => 'Patient payment has been processed successfully.',
                ];
            }
        });
    }

    public function retreiveBooking(array $payload)
    {
        if ($payload['mode'] === 'invoice') {

            $invoice = $this->invoiceRepository->getInvoiceDetails($payload);
            if (! $invoice) {
                return response()->json([
                    'message' => 'Invoice not found.',
                ], 404);
            }
            return new InvoiceResource($invoice);
        } else if ($payload['mode'] === 'booking') {
            $booking = $this->invoiceRepository->getBookingDetail($payload);
            if (!$booking) {
                return response()->json([
                    'message' => 'Booking not found.',
                ], 404);
            };
            return $booking;
        } else if ($payload['mode'] === 'patient') {
            $patient = $this->invoiceRepository->getPatientWithUuid($payload);
            if (! $patient) {
                return response()->json([
                    'message' => 'Patient not found.',
                ], 404);
            };
            return $patient;
        } else {
            throw new Exception("Invalid Input");
        }
    }


    public function retrieveAllBooking(User $user, array $payload)
    {
        return $this->invoiceRepository->getInvoices($payload);
    }
}
