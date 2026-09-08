<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Events\NotificationEvent;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\InvoiceAdjustment;
use App\Models\AdmissionPeriod;
use App\Models\Module;
use App\Models\PatientAdmission;
use App\Models\Refund;
use App\Models\User;
use App\Repository\NotificationRepository;
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
        private NotificationRepository $notificationRepository
    ) {}

    public function getPaidAmount(Invoice $invoice)
    {
        return round(
            (float) $invoice->allocations()->sum('amount'),
            2
        );
    }

    public function getRefundedAmount(Invoice $invoice)
    {
        return round(
            (float) $invoice->allocations()
                ->with('refundAllocations.refund')
                ->get()
                ->flatMap(fn($allocation) => $allocation->refundAllocations)
                ->filter(
                    fn($line) => in_array(
                        $line->refund?->status,
                        Refund::SETTLED_STATUSES,
                        true
                    )
                )
                ->sum('amount'),
            2
        );
    }

    public function getNetPaidAmount(Invoice $invoice)
    {
        return round(max(0,   $this->getPaidAmount($invoice)   - $this->getRefundedAmount($invoice)), 2);
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


    public function getRefundableAmount(Invoice $invoice)
    {
        $invoice->loadMissing('allocations.refundAllocations.refund', 'invoiceAdjustments');

        return round(max(0, $this->getNetPaidAmount($invoice) - (float) $invoice->adjusted_total), 2);
    }


    public function getPendingRefunds(Invoice $invoice)
    {
        return Refund::query()
            ->whereHas(
                'allocations',
                fn($query) => $query->whereIn(
                    'allocation_id',
                    $invoice->allocations()->select('allocation_id')
                )
            )
            ->where('status', Refund::STATUS_REQUESTED)
            ->get();
    }

    public function getRefundSummary(Invoice $invoice): array
    {
        $pending = $this->getPendingRefunds($invoice);
        $refundable = $this->getRefundableAmount($invoice);
        $first = $pending->first();

        return [
            'amount_paid' => $this->getPaidAmount($invoice),
            'refunded_amount' => $this->getRefundedAmount($invoice),
            'retained_amount' => $this->getRetainedAmount($invoice),
            'refundable_amount' => $refundable,
            'has_refundable_balance' => $refundable > 0,
            'requested_refund' => $first ? [
                'refund_id' => $first->refund_id,
                'refund_code' => $first->refund_code,
                'amount' => round((float) $pending->sum('amount'), 2),
                'method' => $first->refund_method,
                'account_details' => $first->masked_card_number,
                'requested_at' => $first->created_at?->toIso8601String(),
            ] : null,
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

    public function createRefundCurrentInvoice(Invoice $invoice, PatientAdmission $admission,  AdmissionPeriod $period)
    {
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

        $existingCredit = InvoiceAdjustment::query()
            ->where('invoice_id', $invoice->invoice_id)
            ->where(
                'type',
                InvoiceAdjustment::TYPE_CORRECTION
            )
            ->exists();

        if ($existingCredit) {
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

        $invoice->syncStatus();
    }

    // A period the patient is discharged before ever reaching is cancelled and
    // paid back in full. There is no judgement call in that, so it happens on
    // every discharge rather than being asked for.
    public function createRefundFutureInvoice(Invoice $invoice, array $payload)
    {
        $existingCredit = InvoiceAdjustment::query()
            ->where('invoice_id', $invoice->invoice_id)
            ->where(
                'type',
                InvoiceAdjustment::TYPE_CORRECTION
            )
            ->exists();

        $adjustedTotal = (float) $invoice->adjusted_total;

        if (!$existingCredit && $adjustedTotal > 0) {
            InvoiceAdjustment::create([
                'invoice_id' => $invoice->invoice_id,
                'type' => InvoiceAdjustment::TYPE_CORRECTION,
                'amount' => round(-$adjustedTotal, 2),
                'reason' => 'Discharged before this period started. Invoice cancelled.',
            ]);
        }

        $invoice->refresh();

        $refundableAmount = $this->getRefundableAmount($invoice);

        if ($refundableAmount <= 0) {
            $invoice->syncStatus();

            return;
        }

        $this->createRefundsForInvoice($invoice, $refundableAmount);
    }

    public function createRefundFull(Invoice $invoice, string $reason)
    {
        $existingAdjustment = InvoiceAdjustment::query()
            ->where('invoice_id', $invoice->invoice_id)
            ->where(
                'type',
                InvoiceAdjustment::TYPE_CORRECTION
            )
            ->exists();

        $adjustedTotal = (float) $invoice->adjusted_total;

        if (!$existingAdjustment && $adjustedTotal > 0) {
            InvoiceAdjustment::create([
                'invoice_id' => $invoice->invoice_id,
                'type' => InvoiceAdjustment::TYPE_CORRECTION,
                'amount' => round(-$adjustedTotal, 2),
                'reason' => $reason,
            ]);
        }

        $invoice->refresh();

        $refundableAmount = $this->getRefundableAmount($invoice);

        if ($refundableAmount <= 0) {
            $invoice->syncStatus();

            return;
        }

        $this->createRefundsForInvoice($invoice, $refundableAmount);
    }


    public function createRefundsForInvoice(Invoice $invoice, float $amount,  string $status = Refund::STATUS_COMPLETED,  ?string $method = null, ?string $accountDetails = null)
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            return;
        }

        $refundableAmount = $this->getRefundableAmount($invoice);

        if ($amount > $refundableAmount) {
            throw new Exception(
                'Refund amount exceeds the refundable amount.',
                422
            );
        }

        $invoice->loadMissing(
            'allocations.refundAllocations.refund',
            'allocations.payment'
        );

        // Worked out first, so one refund is written with its split rather than
        // a separate refund per allocation. The family asked for one refund and
        // it is approved or declined as one.
        $remainingAmount = $amount;
        $split = [];
        $source = null;

        foreach ($invoice->allocations as $allocation) {
            if ($remainingAmount <= 0) {
                break;
            }

            $allocationRefundable = max(
                0,
                (float) $allocation->amount
                    - $allocation->refundedAmount(Refund::SETTLED_STATUSES)
            );

            if ($allocationRefundable <= 0) {
                continue;
            }

            $refundAmount = round(min($remainingAmount, $allocationRefundable), 2);

            if ($refundAmount <= 0) {
                continue;
            }

            $split[] = [
                'allocation_id' => $allocation->allocation_id,
                'amount' => $refundAmount,
            ];

            $source ??= $allocation;

            $remainingAmount = round($remainingAmount - $refundAmount, 2);
        }

        if ($remainingAmount > 0) {
            throw new Exception('Unable to process the requested refund amount.',  422);
        }

        $refund = Refund::create([
            'amount' => $amount,
            'refund_method' => $method ?? $source?->payment?->payment_method,
            'status' => $status,
            'masked_card_number' => $accountDetails
                ?? $source?->payment?->masked_card_number,
        ]);

        foreach ($split as $line) {
            $refund->allocations()->create($line + ['created_at' => now()]);
        }

        $invoice->refresh()->syncStatus();

        return $refund;
    }


    public function requestPortalRefund(object $patient, array $payload, ?User $user = null): array
    {
        $method = trim((string) $payload['method']);
        $accountDetails = MaskUtil::accountDetails(
            $method,
            trim((string) $payload['account_details'])
        );

        $invoice = $patient->patient_invoices
            ->first(fn($invoice) => $this->getRefundableAmount($invoice) > 0
                && $this->getPendingRefunds($invoice)->isEmpty());

        if (!$invoice) {
            throw new Exception(
                'There is no refundable balance to request right now.',
                404
            );
        }

        $refundable = $this->getRefundableAmount($invoice);

        // The family may ask for part of the credit and leave the rest sitting,
        // so an amount is honoured when given and the whole credit is the
        // default. Capped either way: they can never claim more than is theirs.
        $amount = isset($payload['amount'])
            ? round((float) $payload['amount'], 2)
            : $refundable;

        if ($amount <= 0 || $amount > $refundable) {
            throw new Exception(
                'Refund amount must be between 0 and ' . $refundable . '.',
                422
            );
        }

        return DB::transaction(function () use (
            $invoice,
            $amount,
            $method,
            $accountDetails,
            $payload,
            $user
        ) {
            $this->createRefundsForInvoice(
                $invoice,
                $amount,
                Refund::STATUS_REQUESTED,
                $method,
                $accountDetails
            );

            $this->notifyAccounting($invoice, $amount, $user);

            return [
                'success' => true,
                'message' => 'Your refund request has been sent to accounting.',
                'amount' => $amount,
                'invoice_id' => $invoice->invoice_id,
            ];
        });
    }


    private function notifyAccounting(Invoice $invoice, float $amount, ?User $user): void
    {
        $module = Module::where('module_name', ModuleEnum::BillingAndInvoices->value)->first();

        if (!$module) {
            return;
        }

        $recipients = Employee::query()
            ->with('users')
            ->whereHas(
                'employeeBranch',
                fn($q) => $q->where('branch_id', $invoice->branch_id)
            )
            ->whereHas(
                'permissions',
                fn($q) => $q->where('module_id', $module->module_id)
                    ->where('branch_id', $invoice->branch_id)
                    ->where('can_read', true)
            )
            ->get();

        $message = 'A refund of ' . number_format($amount, 2)
            . ' was requested on invoice ' . $invoice->invoice_code . '.';

        foreach ($recipients as $employee) {
            if (!$employee->user_id || !$employee->users?->uuid) {
                continue;
            }

            $this->notificationRepository->create([
                'branch_id' => $invoice->branch_id,
                'to_user_id' => $employee->user_id,
                'from_user_id' => $user?->user_id,
                'message_type' => 'Billing',
                'message' => $message,
            ]);

            event(new NotificationEvent(
                $employee->users->uuid,
                (string) $invoice->branch?->uuid,
                $message,
                (string) $invoice->invoice_id,
                'Billing',
                null,
            ));
        }
    }

    public function createRefundFromDashboard(Invoice $invoice, array $payload): array
    {
        $refundable = $this->getRefundableAmount($invoice);

        if ($refundable <= 0) {
            throw new Exception('This invoice has no refundable balance.', 422);
        }

        $amount = isset($payload['amount'])
            ? round((float) $payload['amount'], 2)
            : $refundable;

        if ($amount <= 0 || $amount > $refundable) {
            throw new Exception(
                'Refund amount must be between 0 and ' . $refundable . '.',
                422
            );
        }

        $method = trim((string) ($payload['method'] ?? ''));
        $accountDetails = $method && !empty($payload['account_details'])
            ? MaskUtil::accountDetails($method, trim((string) $payload['account_details']))
            : null;

        return DB::transaction(function () use ($invoice, $amount, $payload, $method, $accountDetails) {
            $this->getPendingRefunds($invoice)->each->delete();

            $this->createRefundsForInvoice(
                $invoice,
                $amount,
                Refund::STATUS_COMPLETED,
                $method ?: null,
                $accountDetails
            );

            return [
                'success' => true,
                'message' => 'Refund recorded.',
                'amount' => $amount,
            ];
        });
    }

}
