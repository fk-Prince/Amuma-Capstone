<?php

namespace App\Service;

use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Refund;
use App\Models\User;
use App\Repository\InvoiceRepository;
use App\Repository\RefundRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class InvoiceService
{


    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private RefundRepository $refundRepository
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

    public function storeBooking(array $payload)
    {
        return DB::transaction(function () use ($user, $payload) {
            $mode = $payload['mode'];
            if ($mode == 'invoice') {
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

    public function retrieveAllBooking(array $payload)
    {
        return $this->invoiceRepository->getInvoices($payload);
    }

    public function completeRefund(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $refunds = $this->refundRepository->findRefund($payload);

            if ($refunds->isEmpty()) {
                throw new Exception('Refund not found.', 404);
            }


            foreach ($refunds as $refund) {
                if ($refund->status !== Refund::STATUS_PROCESSING) {
                    continue;
                }
                $refund->update([
                    'status' => Refund::STATUS_COMPLETED,
                ]);
            }


            return [
                'message' => 'All refunds have been completed successfully.',
                'data' => $this->invoiceRepository->getPatientWithUuid($payload)
            ];
        });
    }
}
