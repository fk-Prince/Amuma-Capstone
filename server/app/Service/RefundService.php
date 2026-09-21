<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Events\NotificationEvent;
use App\Http\Resources\RefundResource;
use App\Models\AdmissionPeriod;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\InvoiceAdjustment;
use App\Models\Module;
use App\Models\PatientAdmission;
use App\Models\Refund;
use App\Models\Transaction;
use App\Models\User;
use App\Repository\NotificationRepository;
use App\Repository\RefundRepository;
use App\Utils\DischargeCalculator;
use App\Utils\MaskUtil;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class RefundService
{
    private const YEARLY_HALF_REFUND_WINDOW_DAYS = 183;
    private const YEARLY_HALF_REFUND_RATE = 0.50;

    public function __construct(
        private NotificationRepository $notificationRepository,
        private RefundRepository $refundRepository,
        private TransactionService $transactions
    ) {}

    public function getPaidAmount(Invoice $invoice)
    {
        return round((float) $invoice->allocations()->sum('amount'), 2);
    }

    public function getRefundedAmount(Invoice $invoice)
    {
        $invoice->loadMissing('allocations.refundAllocations.refund.transaction');

        return round((float) $invoice->refunded_amount, 2);
    }

    public function getNetPaidAmount(Invoice $invoice)
    {
        return round(max(0, $this->getPaidAmount($invoice) - $this->getRefundedAmount($invoice)), 2);
    }

    public function getRetainedAmount(Invoice $invoice)
    {
        return round(
            (float) InvoiceAdjustment::query()
                ->where('invoice_id', $invoice->invoice_id)
                ->sum('amount'),
            2
        );
    }


    public function getCreditableAmount(Invoice $invoice)
    {
        $invoice->loadMissing('allocations.refundAllocations.refund.transaction', 'invoiceAdjustments');

        return round(max(0, $this->getNetPaidAmount($invoice) - (float) $invoice->adjusted_total), 2);
    }

    public function getRefundableAmount(Invoice $invoice)
    {
        return round(
            (float) $this->refundRepository->forInvoice($invoice)
                ->filter(fn(Refund $credit) => $credit->is_available)
                ->sum('amount'),
            2
        );
    }


    public function creditFromAdjustment(InvoiceAdjustment $adjustment): ?Refund
    {
        if ((float) $adjustment->amount >= 0) {
            return null;
        }

        $invoice = $adjustment->invoice;

        if (!$invoice) {
            return null;
        }

        $invoice->refresh()->load('allocations.refundAllocations.refund.transaction', 'invoiceAdjustments');

        $amount = round(
            min($this->getCreditableAmount($invoice), abs((float) $adjustment->amount)),
            2
        );

        if ($amount <= 0) {
            return null;
        }

        $remaining = $amount;
        $lines = [];

        foreach ($invoice->allocations as $allocation) {
            if ($remaining <= 0) {
                break;
            }

            $available = $allocation->creditableAmount();

            if ($available <= 0) {
                continue;
            }

            $share = round(min($remaining, $available), 2);

            $lines[] = [
                'allocation_id' => $allocation->allocation_id,
                'invoice_adjustment_id' => $adjustment->invoice_adjustment_id,
                'amount' => $share,
            ];

            $remaining = round($remaining - $share, 2);
        }

        if (!$lines) {
            return null;
        }

        $credit = $this->refundRepository->create(
            round($amount - $remaining, 2),
            $lines
        );

        $invoice->refresh()->syncStatus();

        return $credit;
    }

    public function getRefundSummary(Invoice $invoice): array
    {
        $credits = $this->refundRepository->forInvoice($invoice);

        $available = round(
            (float) $credits->filter(fn(Refund $credit) => $credit->is_available)->sum('amount'),
            2
        );

        $pending = $credits->first(
            fn(Refund $credit) => $credit->transaction?->status === Transaction::STATUS_REQUESTED
        )?->transaction;

        return [
            'amount_paid' => $this->getPaidAmount($invoice),
            'refunded_amount' => $this->getRefundedAmount($invoice),
            'retained_amount' => $this->getRetainedAmount($invoice),
            'refundable_amount' => $available,
            'has_refundable_balance' => $available > 0,
            'requested_refund' => $pending ? $this->formatWithdrawal($pending) : null,
        ];
    }

    public function getCancellationRefundAmount(Invoice $invoice, PatientAdmission $admission, AdmissionPeriod $period)
    {
        $paid = $this->getNetPaidAmount($invoice);

        if ($paid <= 0) {
            return 0;
        }

        $days = DischargeCalculator::calculateAdmissionDays(
            $admission->admitted_at
                ? Carbon::parse($admission->admitted_at)
                : null
        );

        if ($days === null) {
            return $paid;
        }

        $contract = $period->branchContract;
        $billingCycle = $contract ? DischargeCalculator::getBillingCycle($contract) : '';

        if ($billingCycle === 'YEARLY' && $days < self::YEARLY_HALF_REFUND_WINDOW_DAYS) {
            $half = round($paid / 2, 2);

            $daysStayedAmount = round(
                ($days / 365) * DischargeCalculator::periodPrice($period),
                2
            );

            return round(max(0, $half - $daysStayedAmount), 2);
        }

        return 0;
    }

    public function getRequiredPaymentAmount(AdmissionPeriod $period,  PatientAdmission $admission)
    {
        $contract = $period->branchContract;

        if (!$contract) {
            return 0;
        }

        $price = DischargeCalculator::getContractPrice($contract);

        if ($price <= 0) {
            return 0;
        }

        $billingCycle = DischargeCalculator::getBillingCycle($contract);

        if ($billingCycle === 'YEARLY' && DischargeCalculator::isWithinYearlyHalfRefundWindow($admission)) {
            return round($price * self::YEARLY_HALF_REFUND_RATE,  2);
        }

        if ($billingCycle === 'MONTHLY') {
            return $price;
        }

        if ($billingCycle === 'YEARLY') {
            return $price;
        }

        return 0;
    }

    public function validateRequiredPayment(Invoice $invoice,  AdmissionPeriod $period, PatientAdmission $admission)
    {
        $paid = $this->getNetPaidAmount($invoice);

        $required = $this->getRequiredPaymentAmount(
            $period,
            $admission
        );

        if ($required <= 0) {
            return;
        }

        if ($paid < $required) {
            $shortfall = round($required - $paid, 2);
            throw new Exception("Required payment has not been met. " . "Paid: {$paid}, " . "Required: {$required}, " . "Short by: {$shortfall}.",   422);
        }
    }

    public function hasRequiredPayment(Invoice $invoice, AdmissionPeriod $period, PatientAdmission $admission)
    {
        return $this->getNetPaidAmount($invoice) >= $this->getRequiredPaymentAmount($period, $admission);
    }

    public function settleDischarge(PatientAdmission $admission, AdmissionPeriod $period, bool $force = false): void
    {
        $plan = DischargeCalculator::plan($admission, $period);

        $shortfall = round((float) collect($plan['invoices'])->sum('owed'), 2);

        if ($shortfall > 0 && !$force) {
            $paid = round((float) collect($plan['invoices'])->sum('net_paid'), 2);
            $required = round((float) collect($plan['invoices'])->sum('new_total'), 2);

            throw new Exception(
                "Required payment has not been met. Paid: {$paid}, Required: {$required}, Short by: {$shortfall}",
                422
            );
        }

        foreach ($plan['invoices'] as $entry) {
            $invoice = $entry['invoice'];

            if ($entry['credit'] >= 0.01 && !($force && $entry['net_paid'] <= 0)) {
                InvoiceAdjustment::create([
                    'invoice_id' => $invoice->invoice_id,
                    'type' => InvoiceAdjustment::TYPE_CORRECTION,
                    'amount' => round(-$entry['credit'], 2),
                    'reason' => 'Discharged early. The unused part of the stay is credited back.',
                ]);
            }

            $invoice->refresh()->syncStatus();

            if (!$entry['has_current'] && $invoice->net_paid_amount <= 0) {
                $invoice->update(['status' => Invoice::STATUS_VOID]);
            }
        }
    }

    public function createRefundCurrentInvoice(Invoice $invoice, PatientAdmission $admission,  AdmissionPeriod $period)
    {
        if (in_array($invoice->status, Invoice::CLOSED_STATUSES, true)) {
            return;
        }

        $calculation = DischargeCalculator::getDischargeCalculation(
            $invoice,
            $admission,
            $period
        );

        if (!$calculation['eligible_for_refund']) {
            return;
        }

        $paid = $calculation['amount_paid'];
        $requiredPayment = $calculation['required_payment'];
        $refundAmount = $calculation['refund_amount'];

        if ($paid < $requiredPayment) {
            throw new Exception(
                "Required payment has not been met. "
                    . "Paid: {$paid}, "
                    . "Required: {$requiredPayment}, "
                    . "Short by: " . round($requiredPayment - $paid, 2),
                422
            );
        }

        if ($refundAmount <= 0) {
            return;
        }

        if ($this->hasCreditNote($invoice)) {
            return;
        }

        $delta = round($requiredPayment - (float) $invoice->adjusted_total, 2);

        if (abs($delta) < 0.01) {
            return;
        }

        InvoiceAdjustment::create([
            'invoice_id' => $invoice->invoice_id,
            'type' => InvoiceAdjustment::TYPE_CORRECTION,
            'amount' => $delta,
            'reason' => 'Discharged early. Invoice reduced to the days stayed.',
        ]);

        $invoice->refresh()->syncStatus();
    }

    public function createRefundFutureInvoice(Invoice $invoice)
    {
        $this->cancelInvoice(
            $invoice,
            'Discharged before this period started. Invoice cancelled.'
        );
    }

    public function createRefundFull(Invoice $invoice, string $reason)
    {
        $this->cancelInvoice($invoice, $reason);
    }

    /*
      Writes the credit note that cancels what is left of the bill. The credit on
      the account follows from the adjustment itself, so nothing here decides how
      much money is owed back.
    */
    private function cancelInvoice(Invoice $invoice, string $reason): void
    {
        if (in_array($invoice->status, Invoice::CLOSED_STATUSES, true)) {
            return;
        }

        $adjustedTotal = (float) $invoice->adjusted_total;

        if (!$this->hasCreditNote($invoice) && $adjustedTotal > 0) {
            InvoiceAdjustment::create([
                'invoice_id' => $invoice->invoice_id,
                'type' => InvoiceAdjustment::TYPE_CORRECTION,
                'amount' => round(-$adjustedTotal, 2),
                'reason' => $reason,
            ]);
        }

        $invoice->refresh()->syncStatus();
    }

    private function hasCreditNote(Invoice $invoice): bool
    {
        return InvoiceAdjustment::query()
            ->where('invoice_id', $invoice->invoice_id)
            ->where('type', InvoiceAdjustment::TYPE_CORRECTION)
            ->exists();
    }

    public function creditFor(mixed $patientId): float
    {
        return $this->refundRepository->creditFor($patientId);
    }

    /*
      Hands the credit on the account over to a withdrawal. Whole credits are
      claimed in the order they were granted, and the last one is split when the
      family asks for less than it holds.
    */
    public function withdraw(
        object $patient,
        array $payload,
        string $status = Transaction::STATUS_COMPLETED,
        ?User $user = null
    ): array {
        $available = $this->refundRepository->creditFor($patient->patient_id);

        if ($available <= 0) {
            throw new Exception('There is no credit on this account to withdraw.', 422);
        }

        if (
            $status === Transaction::STATUS_REQUESTED
            && $this->refundRepository->openWithdrawalFor($patient->patient_id)
        ) {
            throw new Exception('A withdrawal is already awaiting a decision.', 422);
        }

        $amount = isset($payload['amount']) && (float) $payload['amount'] > 0
            ? round((float) $payload['amount'], 2)
            : $available;

        if ($amount > $available) {
            throw new Exception(
                'The amount must be between 0 and ' . $available . '.',
                422
            );
        }

        $method = trim((string) ($payload['method'] ?? ''));

        $accountDetails = $method && !empty($payload['account_details'])
            ? MaskUtil::accountDetails($method, trim((string) $payload['account_details']))
            : null;

        $accountName = trim((string) ($payload['account_name'] ?? '')) ?: null;

        return DB::transaction(function () use (
            $patient,
            $amount,
            $status,
            $method,
            $accountDetails,
            $accountName,
            $user
        ) {
            $credits = $this->refundRepository->availableFor($patient->patient_id);

            $branchId = $credits
                ->flatMap(fn(Refund $credit) => $credit->allocations)
                ->map(fn($line) => $line->allocation?->invoice?->branch_id)
                ->filter()
                ->first() ?? $patient->branch_id;

            $withdrawal = $this->transactions->forWithdraw(
                $amount,
                $branchId,
                $patient->patient_id,
                'Withdrawal of credit on the account.',
                $status,
                [
                    'client_id' => $user?->client?->client_id,
                    'method' => $method ?: null,
                    'party_name' => $accountName,
                    'masked_account_number' => $accountDetails,
                ]
            );

            $this->claimCredits($credits, $withdrawal, $amount);

            if ($status === Transaction::STATUS_REQUESTED) {
                $this->notifyAccounting($withdrawal, $patient, $amount, $user);
            }

            return [
                'success' => true,
                'message' => $status === Transaction::STATUS_REQUESTED
                    ? 'Your withdrawal request has been sent to the branch for review.'
                    : 'Withdrawal recorded.',
                'amount' => $amount,
                'request' => $this->formatWithdrawal($withdrawal->refresh()),
            ];
        });
    }

    public function claimCredits(mixed $credits, Transaction $transaction, float $amount): float
    {
        $remaining = round($amount, 2);

        foreach ($credits as $credit) {
            if ($remaining <= 0) {
                break;
            }

            $value = round((float) $credit->amount, 2);

            $claimed = $value <= $remaining
                ? $credit
                : $this->refundRepository->split($credit, $remaining);

            $this->refundRepository->claim($claimed, $transaction);

            $remaining = round($remaining - (float) $claimed->amount, 2);
        }

        return round($amount - $remaining, 2);
    }

    public function requestPortalRefund(object $patient, array $payload, ?User $user = null): array
    {
        return $this->withdraw(
            $patient,
            $payload,
            Transaction::STATUS_REQUESTED,
            $user
        );
    }

    public function requestsForPatient(object $patient): array
    {
        $withdrawals = $this->refundRepository->withdrawalsFor($patient->patient_id);

        return [
            'requests' => $withdrawals
                ->map(fn(Transaction $withdrawal) => $this->formatWithdrawal($withdrawal))
                ->values()
                ->all(),
            'has_open_request' => $withdrawals->contains(
                fn(Transaction $withdrawal) => $withdrawal->status === Transaction::STATUS_REQUESTED
            ),
            'available_credit' => $this->refundRepository->creditFor($patient->patient_id),
        ];
    }

    public function formatWithdrawal(?Transaction $withdrawal): ?array
    {
        return RefundResource::format($withdrawal);
    }

    private function notifyAccounting(
        Transaction $withdrawal,
        object $patient,
        float $amount,
        ?User $user
    ): void {
        $module = Module::where('module_name', ModuleEnum::BillingAndInvoices->value)->first();

        if (!$module || !$withdrawal->branch_id) {
            return;
        }

        $recipients = Employee::query()
            ->with('users')
            ->whereHas(
                'employeeBranch',
                fn($q) => $q->where('branch_id', $withdrawal->branch_id)
                    ->where('role_name', 'accounting')
            )
            ->whereHas(
                'permissions',
                fn($q) => $q->where('module_id', $module->module_id)
                    ->where('branch_id', $withdrawal->branch_id)
                    ->where('can_read', true)
            )
            ->get();

        $name = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''));

        $message = 'A withdrawal of ' . number_format($amount, 2)
            . ' in credit was requested'
            . ($name !== '' ? ' for ' . $name : '') . '.';

        foreach ($recipients as $employee) {
            if (!$employee->user_id || !$employee->users?->uuid) {
                continue;
            }

            $this->notificationRepository->create([
                'branch_id' => $withdrawal->branch_id,
                'to_user_id' => $employee->user_id,
                'from_user_id' => $user?->user_id,
                'message_type' => 'Billing',
                'message' => $message,
            ]);

            event(new NotificationEvent(
                $employee->users->uuid,
                (string) $withdrawal->branch?->uuid,
                $message,
                (string) $withdrawal->transaction_id,
                'Billing',
                null,
            ));
        }
    }

    public function withdrawFromDashboard(object $patient, array $payload): array
    {
        return $this->withdraw($patient, $payload, Transaction::STATUS_COMPLETED);
    }
}
