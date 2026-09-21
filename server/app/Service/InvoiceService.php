<?php

namespace App\Service;

use App\Models\Transaction;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentReceiptResource;
use App\Models\Invoice;
use App\Models\InvoiceAdjustment;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Repository\InvoiceRepository;
use App\Repository\PaymentRepository;
use App\Repository\RefundRepository;
use App\Utils\AccommodationHelper;
use App\Utils\InvoiceMoney;
use App\Utils\MaskUtil;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private RefundRepository $refundRepository,
        private PaymentRepository $paymentRepository,
        private TransactionService $transactions,
        private RefundService $refunds
    ) {}

    public function overview(array $payload)
    {
        return $this->invoiceRepository->overview($payload);
    }

    public function createInvoiceService(array $services, string $branchId)
    {
        $invoice = $this->invoiceRepository->create([
            'status' => Invoice::STATUS_PENDING,
            'total_amount' => 0,
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
            'total_amount' => $total
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

                $codes = array_filter((array) ($payload['invoice_codes'] ?? []));

                $invoices = $codes
                    ? $result['invoices']->filter(
                        fn($invoice) => in_array($invoice->invoice_code, $codes, true)
                    )->values()
                    : $result['invoices'];

                if ($codes && $invoices->isEmpty()) {
                    throw new Exception('The selected invoices are no longer payable.', 422);
                }

                return $this->collectCash(
                    $invoices,
                    $payload,
                    $user,
                    'Patient payment has been processed successfully.'
                );
            }

            throw new Exception('Invalid payment mode.', 422);
        });
    }



    public function collectForInvoices(
        Collection $invoices,
        array $payload,
        ?User $user = null,
        string $message = 'Payment recorded successfully.'
    ): array {
        return $this->collectCash($invoices, $payload, $user, $message);
    }

    private function collectCash(
        Collection $invoices,
        array $payload,
        ?User $user,
        string $message
    ): array {
        $cash = round((float) ($payload['cash'] ?? 0), 2);
        $useCredit = (bool) ($payload['use_credit'] ?? false);

        if ($cash <= 0 && !$useCredit) {
            throw new Exception('Enter a cash amount greater than 0.', 422);
        }

        if ($cash > 0 && $useCredit) {
            throw new Exception('Pay with credit or with cash, not both.', 422);
        }

        $allocations = collect($payload['allocations'] ?? [])
            ->map(fn($amount) => round((float) $amount, 2))
            ->filter(fn($amount) => $amount > 0);

        $payable = $invoices
            ->filter(fn($invoice) => $invoice->balance_due > 0)
            ->when(
                $allocations->isNotEmpty(),
                fn($list) => $list->filter(
                    fn($invoice) => $allocations->has($invoice->invoice_code)
                )
            )
            ->sortBy(fn($invoice) => $invoice->paymentOrder())
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


        $credit = $useCredit
            ? $this->applyCredit(
                $patient,
                $payable,
                $totalBalance,
                $user,
                (float) ($payload['credit_amount'] ?? 0) > 0
                    ? round((float) $payload['credit_amount'], 2)
                    : null
            )
            : ['applied' => 0.0, 'payment' => null];

        $creditApplied = $credit['applied'];

        if ($creditApplied > 0) {
            $payable = $payable
                ->each(fn($invoice) => $invoice->refresh())
                ->filter(fn($invoice) => $invoice->balance_due > 0)
                ->values();
        }

        $balanceAfterCredit = round($totalBalance - $creditApplied, 2);

        if ($cash <= 0 || $payable->isEmpty()) {
            return [
                'message' => $creditApplied > 0
                    ? 'Credit applied to the outstanding balance.'
                    : $message,
                'change' => 0,
                'credit_applied' => $creditApplied,
                'invoice_ids' => $credit['payment']
                    ? $credit['payment']->allocations
                    ->filter(fn($allocation) => (float) $allocation->amount > 0)
                    ->pluck('invoice_id')
                    ->all()
                    : [],
                'remaining_balance' => $balanceAfterCredit,
                'receipt' => $this->receiptResource($credit['payment']),
            ];
        }

        $method = trim((string) ($payload['payment_method'] ?? 'CASH'));

        $reference = trim((string) ($payload['reference'] ?? ''));

        $maskedReference = $reference !== ''
            ? MaskUtil::accountDetails($method, $reference)
            : null;


        $transaction = $this->transactions->forPayment(
            $cash,
            $payable->first()->branch_id,
            $patient->patient_id,
            'Payment received at the branch.',
            [
                'method' => $method,
                'party_name' => trim((string) ($payload['payor_name'] ?? '')) ?: null,
                'masked_account_number' => $maskedReference,
            ]
        );

        $receipt = $this->paymentRepository->create([
            'transaction_id'        => $transaction->transaction_id,
            'prior_balance'         => $balanceAfterCredit,
            'cash_tendered'         => $cash,
            'issued_by'             => $user?->user_id,
            'created_at'            => now(),
        ]);

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

            $requested = $allocations->isNotEmpty()
                ? (float) ($allocations[$invoice->invoice_code] ?? 0)
                : $remaining;

            if ($requested <= 0) {
                continue;
            }

            $paymentAmount = round(min($remaining, $priorBalance, $requested), 2);

            $receipt->allocations()->create([
                'invoice_id' => $invoice->invoice_id,
                'amount' => $paymentAmount,
                'description' => $invoice->paymentDescription(),
                'created_at' => now(),
            ]);

            $remaining = round($remaining - $paymentAmount, 2);

            $invoice->refresh();

            $invoice->syncStatus();

            AccommodationHelper::activate($invoice);

            $paidInvoiceIds[] = $invoice->invoice_id;
        }

        $applied = round($cash - $remaining, 2);

        // The transaction was opened for the cash handed over, but change goes
        // straight back out — the ledger only ever counts what the branch
        // actually kept.
        if (abs($applied - (float) $transaction->amount) > 0.001) {
            $transaction->update(['amount' => $applied]);
        }

        // The balance the receipt leaves behind, recorded once on the
        // payment rather than on every line it settled.
        $receipt->update([
            'new_balance' => round($balanceAfterCredit - $applied, 2),
        ]);

        return [
            'message' => $message,
            'change' => $remaining,
            'credit_applied' => $creditApplied,
            'invoice_ids' => $paidInvoiceIds,
            'remaining_balance' => round($balanceAfterCredit - $applied, 2),
            'receipt' => $this->receiptResource($receipt),
            'credit_receipt' => $this->receiptResource($credit['payment']),
        ];
    }

    private function receiptResource(?Payment $payment): ?PaymentReceiptResource
    {
        if (!$payment) {
            return null;
        }

        return new PaymentReceiptResource(
            $payment->load([
                'allocations.invoice.invoiceServices.scheduleService.service',
                'allocations.invoice.invoiceAdmissionLines.admissionPeriod.branchContract',
                'allocations.invoice.invoiceAdmissionLines.admissionPeriod.patientAdmission.bed.room',
                'transaction.branch.location',
                'transaction.patient',
                'issuedBy',
            ])
        );
    }


    public function applyCredit(
        Patient $patient,
        Collection $payable,
        float $priorBalance,
        ?User $user = null,
        ?float $limit = null
    ): array {
        $none = ['applied' => 0.0, 'payment' => null];

        $available = $this->refundRepository->creditFor($patient->patient_id);

        if ($available <= 0) {
            return $none;
        }

        $budget = $limit === null
            ? $available
            : round(min($limit, $available), 2);

        $settled = [];
        $applied = 0.0;

        foreach ($payable as $target) {
            if ($budget <= 0) {
                break;
            }

            $target->refresh()->load('allocations.refundAllocations.refund.transaction', 'invoiceAdjustments');

            $due = round((float) $target->balance_due, 2);

            if ($due <= 0) {
                continue;
            }

            $move = round(min($due, $budget), 2);

            $settled[$target->invoice_id] = $move;

            $budget = round($budget - $move, 2);
            $applied = round($applied + $move, 2);
        }

        if ($applied <= 0) {
            return $none;
        }

        $transaction = $this->transactions->forPayment(
            $applied,
            $payable->first()->branch_id,
            $patient->patient_id,
            'Credit on the account applied to an outstanding invoice.',
            [
                'method' => Payment::METHOD_CREDIT,
                'party_name' => trim(
                    ($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')
                ) ?: null,
            ]
        );

        $this->refunds->claimCredits(
            $this->refundRepository->availableFor($patient->patient_id),
            $transaction,
            $applied
        );

        $payment = $this->paymentRepository->create([
            'transaction_id' => $transaction->transaction_id,
            'prior_balance'  => $priorBalance,
            'new_balance'    => round($priorBalance - $applied, 2),
            'cash_tendered'  => $applied,
            'issued_by'      => $user?->user_id,
            'created_at'     => now(),
        ]);

        foreach ($settled as $invoiceId => $amount) {
            $payment->allocations()->create([
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'description' => $payable
                    ->firstWhere('invoice_id', $invoiceId)
                    ?->paymentDescription(),
                'created_at' => now(),
            ]);
        }

        foreach (array_keys($settled) as $invoiceId) {
            $target = $payable->firstWhere('invoice_id', $invoiceId);

            if (!$target) {
                continue;
            }

            $target->refresh()->syncStatus();

            AccommodationHelper::activate($target);
        }

        return ['applied' => $applied, 'payment' => $payment];
    }

    private function resolvePatient(Invoice $invoice): ?Patient
    {
        $invoice->loadMissing([
            'invoiceAdmissionLines.admissionPeriod.patientAdmission.patient',
            'invoiceServices.scheduleService.schedule.patient',
        ]);

        $patient = $invoice->invoiceAdmissionLines
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

        $receipts = Payment::whereHas(
            'transaction',
            fn($query) => $query->where('branch_id', $payload['branch_id'])
        )
            ->when($search !== '', function ($query) use ($search) {
                $term = '%' . $search . '%';

                $query->where(function ($q) use ($term, $search) {
                    $q->whereHas(
                        'transaction',
                        fn($t) => $t->where('transaction_code', 'ilike', $term)
                            ->orWhere('party_name', 'ilike', $term)
                            ->orWhere('transaction_reference_id', 'ilike', $term)
                    )
                        ->orWhereHas(
                            'transaction.patient',
                            fn($p) => $p
                                ->whereRaw("concat(first_name, ' ', last_name) ilike ?", [$term])
                                ->orWhere('patient_code', 'ilike', "{$search}%")
                        )
                        ->orWhereHas(
                            'allocations.invoice',
                            fn($i) => $i->where('invoice_code', 'ilike', $term)
                        );
                });
            })
            ->with([
                'allocations.invoice.invoiceServices.scheduleService.service',
                'allocations.invoice.invoiceAdmissionLines.admissionPeriod.branchContract',
                'allocations.invoice.invoiceAdmissionLines.admissionPeriod.patientAdmission.bed.room',
                'transaction.branch.location',
                'transaction.patient',
                'transaction.client',
                'issuedBy',
            ])
            ->orderByDesc('payment_id')
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
            $withdrawal = $this->openWithdrawal($payload);

            $this->transactions->settle(
                $withdrawal,
                Transaction::STATUS_COMPLETED
            );

            return [
                'message' => 'The withdrawal has been completed successfully.',
                'data' => $this->invoiceRepository->getPatientWithUuid($payload)
            ];
        });
    }

    public function declineRefund(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $reason = trim((string) ($payload['reason'] ?? ''));

            if ($reason === '') {
                throw new Exception('A reason is required to decline a withdrawal.', 422);
            }

            $withdrawal = $this->openWithdrawal($payload);

            $this->transactions->settle(
                $withdrawal,
                Transaction::STATUS_REJECTED,
                $reason
            );

            return [
                'message' => 'The withdrawal request has been declined.',
                'data' => $this->invoiceRepository->getPatientWithUuid($payload)
            ];
        });
    }

    private function openWithdrawal(array $payload): Transaction
    {
        $withdrawal = $this->refundRepository->findWithdrawal(
            $payload['refund_id'] ?? null
        );

        if (!$withdrawal) {
            throw new Exception('Withdrawal not found.', 404);
        }

        if ($withdrawal->status !== Transaction::STATUS_REQUESTED) {
            throw new Exception('This withdrawal has already been settled.', 422);
        }

        return $withdrawal;
    }

    public function voidInvoice(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $invoice = Invoice::where('invoice_code', $payload['invoice_code'])
                ->where('branch_id', $payload['branch_id'])
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                throw new Exception('Invoice not found.', 404);
            }

            if ($invoice->status === Invoice::STATUS_VOID) {
                throw new Exception('This invoice is already void.', 422);
            }

            if ($invoice->status === Invoice::STATUS_WRITTEN_OFF) {
                throw new Exception('This invoice has been written off and can no longer be voided.', 422);
            }

            if ($invoice->status === Invoice::STATUS_PAID) {
                throw new Exception('A paid invoice can no longer be voided. Issue a refund or credit instead.', 422);
            }

            $reason = trim((string) ($payload['reason'] ?? ''));

            if ($reason === '') {
                throw new Exception('A reason is required to void an invoice.', 422);
            }

            $upgrades = $this->invoiceRepository->upgradesOf($invoice);

            $paidUpgrade = $upgrades->first(fn($upgrade) => (float) $upgrade->net_paid_amount > 0);

            if ($paidUpgrade) {
                throw new Exception(
                    "{$paidUpgrade->invoice_code} was issued for an accommodation upgrade on this invoice and already has a payment. Void it first.",
                    422
                );
            }

            foreach ($upgrades as $upgrade) {
                $this->closeAsVoid($upgrade, $reason, $payload['user_id'] ?? null);
            }

            $this->closeAsVoid($invoice, $reason, $payload['user_id'] ?? null);

            return [
                'message' => $upgrades->isEmpty()
                    ? 'Invoice voided successfully.'
                    : 'Invoice voided successfully along with its upgrade invoice ' . $upgrades->pluck('invoice_code')->implode(', ') . '.',
                'invoice_code' => $invoice->invoice_code,
                'data' => !empty($payload['p_uuid'])
                    ? $this->invoiceRepository->getPatientWithUuid($payload)
                    : null,
            ];
        });
    }

    private function closeAsVoid(Invoice $invoice, string $reason, ?int $userId): void
    {
        InvoiceAdjustment::create([
            'invoice_id' => $invoice->invoice_id,
            'type' => InvoiceAdjustment::TYPE_VOID,
            'amount' => round(-(float) $invoice->adjusted_total, 2),
            'reason' => $reason,
        ]);

        $invoice->update([
            'status' => Invoice::STATUS_VOID,
            'voided_at' => now(),
            'voided_by' => $userId,
            'void_reason' => $reason,
        ]);
    }

    public function adjustInvoice(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $invoice = Invoice::where('invoice_code', $payload['invoice_code'])
                ->where('branch_id', $payload['branch_id'])
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                throw new Exception('Invoice not found.', 404);
            }

            if (in_array($invoice->status, Invoice::CLOSED_STATUSES, true)) {
                throw new Exception('This invoice is void or written off and can no longer be adjusted.', 422);
            }

            $reason = trim((string) ($payload['reason'] ?? ''));

            if ($reason === '') {
                throw new Exception('A reason is required to adjust an invoice.', 422);
            }

            $amount = (float) ($payload['amount'] ?? 0);

            if ($amount === 0.0) {
                throw new Exception('Enter a non-zero adjustment amount.', 422);
            }

            if ($amount < 0 && abs($amount) > (float) $invoice->adjusted_total + 0.01) {
                throw new Exception("The deduction can't exceed the invoice's billed total.", 422);
            }

            InvoiceAdjustment::create([
                'invoice_id' => $invoice->invoice_id,
                'type' => InvoiceAdjustment::TYPE_CORRECTION,
                'amount' => round($amount, 2),
                'reason' => $reason,
            ]);

            $invoice->syncStatus();

            return [
                'message' => 'Invoice adjusted successfully.',
                'invoice_code' => $invoice->invoice_code,
                'data' => !empty($payload['p_uuid'])
                    ? $this->invoiceRepository->getPatientWithUuid($payload)
                    : null,
            ];
        });
    }

    // No InvoiceAdjustment here, unlike void: that would hand the patient a credit for a debt the branch is writing off, not refunding.
    public function writeOffInvoice(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $invoice = Invoice::where('invoice_code', $payload['invoice_code'])
                ->where('branch_id', $payload['branch_id'])
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                throw new Exception('Invoice not found.', 404);
            }

            if ($invoice->status === Invoice::STATUS_VOID) {
                throw new Exception('A voided invoice cannot be written off.', 422);
            }

            if ($invoice->status === Invoice::STATUS_WRITTEN_OFF) {
                throw new Exception('This invoice is already written off.', 422);
            }

            $reason = trim((string) ($payload['reason'] ?? ''));

            if ($reason === '') {
                throw new Exception('A reason is required to write off an invoice.', 422);
            }

            $invoice->update([
                'status' => Invoice::STATUS_WRITTEN_OFF,
                'written_off_at' => now(),
                'written_off_by' => $payload['user_id'] ?? null,
                'write_off_reason' => $reason,
            ]);

            return [
                'message' => 'Invoice written off successfully.',
                'invoice_code' => $invoice->invoice_code,
                'data' => !empty($payload['p_uuid'])
                    ? $this->invoiceRepository->getPatientWithUuid($payload)
                    : null,
            ];
        });
    }
}
