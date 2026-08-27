<?php

namespace App\Service;

use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentReceiptResource;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PaymentReceipt;
use App\Models\Refund;
use App\Models\User;
use App\Repository\InvoiceRepository;
use App\Repository\RefundRepository;
use App\Utils\MaskUtil;
use Exception;
use Illuminate\Support\Collection;
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

    public function storeBooking(array $payload, ?User $user = null)
    {
        return DB::transaction(function () use ($user, $payload) {
            $mode = $payload['mode'] ?? null;

            if ($mode === 'invoice') {
                $invoice = $this->invoiceRepository->findByField([
                    ['invoice_code', '=', $payload['invoice_code']]
                ]);

                if (!$invoice) {
                    throw new Exception("Invoice not found", 404);
                }

                return $this->collectCash(
                    collect([$invoice]),
                    $payload,
                    $user,
                    'Invoice payment has been processed successfully.'
                );
            }

            if ($mode === 'patient') {
                $result = $this->invoiceRepository->getUnpaidInvoiceByPatient(
                    $payload['p_uuid'],
                    $payload['branch_id']
                );

                return $this->collectCash(
                    $result['invoices'],
                    $payload,
                    $user,
                    'Patient payment has been processed successfully.'
                );
            }

            throw new Exception('Invalid payment mode.', 422);
        });
    }


    private function collectCash(
        Collection $invoices,
        array $payload,
        ?User $user,
        string $message
    ): array {
        $cash = round((float) ($payload['cash'] ?? 0), 2);

        if ($cash <= 0) {
            throw new Exception('Enter a cash amount greater than 0.', 422);
        }

        $payable = $invoices
            ->filter(fn($invoice) => $invoice->balance_due > 0)
            ->sortBy('created_at')
            ->values();

        if ($payable->isEmpty()) {
            throw new Exception('There is no outstanding balance to pay.', 422);
        }

        $totalBalance = round((float) $payable->sum('balance_due'), 2);

        $patient = $this->resolvePatient($payable->first());

        if (!$patient) {
            throw new Exception(
                'Unable to determine which patient this invoice belongs to.',
                422
            );
        }

        $method = trim((string) ($payload['payment_method'] ?? 'CASH'));

        $reference = trim((string) ($payload['reference'] ?? ''));

        $receipt = PaymentReceipt::create([
            'branch_id'       => $payable->first()->branch_id,
            'patient_id'      => $patient->patient_id,
            'client_id'       => null,
            'payor_name'      => trim((string) ($payload['payor_name'] ?? ''))
                ?: trim(
                    ($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')
                ) ?: null,
            'amount_tendered' => $cash,
            'balance_before'  => $totalBalance,
            'issued_by'       => $user?->user_id,
            'created_at'      => now(),
        ]);

        $maskedReference = $reference !== ''
            ? MaskUtil::accountDetails($method, $reference)
            : null;

        $remaining = $cash;
        $paidInvoiceIds = [];

        foreach ($payable as $invoice) {
            if ($remaining <= 0) {
                break;
            }

            $priorBalance = $invoice->balance_due;

            if ($priorBalance <= 0) {
                continue;
            }

            $paymentAmount = round(min($remaining, $priorBalance), 2);

            $payment = $invoice->payments()->create([
                'receipt_id' => $receipt->receipt_id,
                'amount' => $paymentAmount,
                'payment_method' => $method,
                'masked_card_number' => $maskedReference,
                'prior_balance' => $priorBalance,
            ]);

            $remaining = round($remaining - $paymentAmount, 2);

            $invoice->refresh();

            $status = $invoice->balance_due <= 0
                ? Invoice::STATUS_PAID
                : Invoice::STATUS_PARTIAL;

            $invoice->update([
                'status' => $status,
            ]);

            $payment->update([
                'new_balance' => $invoice->balance_due,
            ]);

            $paidInvoiceIds[] = $invoice->invoice_id;
        }

        $applied = round($cash - $remaining, 2);

        return [
            'message' => $message,
            'change' => $remaining,
            'invoice_ids' => $paidInvoiceIds,
            'remaining_balance' => round($totalBalance - $applied, 2),
            'receipt' => new PaymentReceiptResource(
                $receipt->load([
                    'payments.invoice',
                    'branch.location',
                    'patient',
                    'issuer',
                ])
            ),
        ];
    }

    private function resolvePatient(Invoice $invoice): ?Patient
    {
        $invoice->loadMissing([
            'invoiceFacility.patientAdmission.patient',
            'invoiceServices.scheduleService.schedule.patient',
        ]);

        $patient = $invoice->invoiceFacility
            ->first()?->patientAdmission?->patient;

        if ($patient) {
            return $patient;
        }

        return $invoice->invoiceServices
            ->first()?->scheduleService?->schedule?->patient;
    }

    public function receipts(array $payload)
    {
        $search = trim((string) ($payload['search'] ?? ''));
        $perPage = (int) ($payload['per_page'] ?? 10);

        $receipts = PaymentReceipt::where('branch_id', $payload['branch_id'])
            ->when($search !== '', function ($query) use ($search) {
                $term = '%' . $search . '%';

                $query->where(function ($q) use ($term) {
                    $q->where('receipt_no', 'ilike', $term)
                        ->orWhere('payor_name', 'ilike', $term)
                        ->orWhereHas(
                            'patient',
                            fn($p) => $p
                                ->whereRaw("concat(first_name, ' ', last_name) ilike ?", [$term])
                        )
                        ->orWhereHas(
                            'payments',
                            fn($pay) => $pay
                                ->where('reference_id', 'ilike', $term)
                                ->orWhereHas(
                                    'invoice',
                                    fn($i) => $i->where('invoice_code', 'ilike', $term)
                                )
                        );
                });
            })
            ->with([
                'payments.invoice',
                'branch.location',
                'patient',
                'client',
                'issuer',
            ])
            ->orderByDesc('receipt_id')
            ->paginate($perPage);

        return PaymentReceiptResource::collection($receipts);
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
