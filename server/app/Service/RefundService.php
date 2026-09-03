<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Events\NotificationEvent;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\InvoiceAdjustment;
use App\Models\InvoiceAccommodation;
use App\Models\Module;
use App\Models\PatientAdmission;
use App\Models\Refund;
use App\Models\User;
use App\Repository\NotificationRepository;
use App\Utils\MaskUtil;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(
        private NotificationRepository $notificationRepository
    ) {}

    private const TERMINATION_FEE_WINDOW_DAYS = 7;


    private function terminationFeeRate(?int $branchId): float
    {
        if (!$branchId) {
            return 0.0;
        }

        $percent = (float) data_get(
            Branch::find($branchId)?->settings,
            'termination_fee_percent',
            0
        );

        return max(0.0, min(100.0, $percent)) / 100;
    }

    private const YEARLY_HALF_REFUND_WINDOW_DAYS = 183;
    private const YEARLY_HALF_REFUND_RATE = 0.50;

    public function getPaidAmount(Invoice $invoice): float
    {
        return round(
            (float) $invoice->payments()->sum('amount'),
            2
        );
    }

    public function getRefundedAmount(Invoice $invoice): float
    {
        return round(
            (float) $invoice->payments()
                ->with('refunds')
                ->get()
                ->flatMap(fn($payment) => $payment->refunds)
                ->whereIn('status', [
                    Refund::STATUS_COMPLETED,
                    Refund::STATUS_PROCESSING,
                ])
                ->sum('amount'),
            2
        );
    }

    public function getNetPaidAmount(Invoice $invoice): float
    {
        return round(max(0,   $this->getPaidAmount($invoice)   - $this->getRefundedAmount($invoice)), 2);
    }

    public function getRetainedAmount(Invoice $invoice): float
    {
        return round(
            (float) InvoiceAdjustment::query()
                ->where('invoice_id', $invoice->invoice_id)
                ->sum('amount'),
            2
        );
    }

    // Nothing is written when an invoice becomes refundable — the balance is
    // read back from what was paid, what has already been refunded, and what
    // the adjustments retain.
    public function getRefundableAmount(Invoice $invoice): float
    {
        return round(
            max(
                0,
                $this->getPaidAmount($invoice)
                    - $this->getRefundedAmount($invoice)
                    - $this->getRetainedAmount($invoice)
            ),
            2
        );
    }

    // A family's request is just a refund parked at 'pending': it is not money
    // moved yet, so it never counts against the refundable balance.
    public function getPendingRefunds(Invoice $invoice)
    {
        return Refund::query()
            ->whereIn(
                'payment_id',
                $invoice->payments()->select('payment_id')
            )
            ->where('status', Refund::STATUS_PENDING)
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
                'amount' => round((float) $pending->sum('amount'), 2),
                'method' => $first->refund_method,
                'account_details' => $first->masked_card_number,
                'reason' => $first->reason,
                'requested_at' => $first->created_at?->toIso8601String(),
            ] : null,
        ];
    }


    public function getCancellationRefundAmount(
        Invoice $invoice,
        PatientAdmission $admission,
        InvoiceAccommodation $invoiceAccommodation
    ): float {
        $paid = $this->getNetPaidAmount($invoice);

        if ($paid <= 0) {
            return 0;
        }

        $days = $this->calculateAdmissionDays(
            $admission->admitted_at
                ? Carbon::parse($admission->admitted_at)
                : null
        );

        if ($days === null) {
            return $paid;
        }

        if ($days < self::TERMINATION_FEE_WINDOW_DAYS) {
            $retain = round(
                $paid * $this->terminationFeeRate($invoice->branch_id),
                2
            );

            return round(max(0, $paid - $retain), 2);
        }

        $contract = $invoiceAccommodation->branchContract;
        $billingCycle = $contract ? $this->getBillingCycle($contract) : '';

        if ($billingCycle === 'YEARLY' && $days < self::YEARLY_HALF_REFUND_WINDOW_DAYS) {
            $half = round($paid / 2, 2);

            $daysStayedAmount = round(
                ($days / 365) * (float) $invoiceAccommodation->price,
                2
            );

            return round(max(0, $half - $daysStayedAmount), 2);
        }

        return 0;
    }

    public function getRequiredPaymentAmount(InvoiceAccommodation $invoiceAccommodation,  PatientAdmission $admission)
    {
        $contract = $invoiceAccommodation->branchContract;

        if (!$contract) {
            return 0;
        }

        $price = $this->getContractPrice($contract);

        if ($price <= 0) {
            return 0;
        }

        $billingCycle = $this->getBillingCycle($contract);

        if ($this->isWithinTerminationFeeWindow($admission)) {
            return round(
                $price * $this->terminationFeeRate($contract->branch_id),
                2
            );
        }

        if ($billingCycle === 'YEARLY' && $this->isWithinYearlyHalfRefundWindow($admission)) {
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

    public function validateRequiredPayment(Invoice $invoice,  InvoiceAccommodation $invoiceAccommodation, PatientAdmission $admission)
    {
        $paid = $this->getNetPaidAmount($invoice);

        $required = $this->getRequiredPaymentAmount(
            $invoiceAccommodation,
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

    public function hasRequiredPayment(Invoice $invoice, InvoiceAccommodation $invoiceAccommodation, PatientAdmission $admission)
    {
        return $this->getNetPaidAmount($invoice) >= $this->getRequiredPaymentAmount($invoiceAccommodation, $admission);
    }

    public function createRefundCurrentInvoice(Invoice $invoice,    PatientAdmission $admission,  InvoiceAccommodation $invoiceAccommodation)
    {
        $calculation = $this->getDischargeCalculation(
            $invoice,
            $admission,
            $invoiceAccommodation
        );

        if (!$calculation['eligible_for_refund']) {
            return;
        }

        $paid = $calculation['amount_paid'];
        $requiredPayment = $calculation['required_payment'];
        $refundAmount = $calculation['refund_amount'];
        $terminationFee = $calculation['termination_fee_amount'];

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

        $existingTerminationFee = InvoiceAdjustment::query()
            ->where('invoice_id', $invoice->invoice_id)
            ->where(
                'type',
                InvoiceAdjustment::TYPE_TERMINATION_FEE
            )
            ->exists();

        if (!$existingTerminationFee && $terminationFee > 0) {
            InvoiceAdjustment::create([
                'invoice_id' => $invoice->invoice_id,
                'type' => InvoiceAdjustment::TYPE_TERMINATION_FEE,
                'amount' => $terminationFee,
                'reason' => 'Termination fee due to early admission termination',
            ]);
        }
    }

    public function createRefundFutureInvoice(Invoice $invoice, array $payload)
    {
        if (empty($payload['refund'])) {
            return;
        }

        $refundableAmount = $this->getRefundableAmount($invoice);

        if ($refundableAmount <= 0) {
            return;
        }

        $this->createRefundsForInvoice($invoice, $refundableAmount,  'Future invoice refunded due to admission discharge.');

        $existingTerminationFee = InvoiceAdjustment::query()
            ->where('invoice_id', $invoice->invoice_id)
            ->where(
                'type',
                InvoiceAdjustment::TYPE_TERMINATION_FEE
            )
            ->exists();

        if (!$existingTerminationFee) {
            InvoiceAdjustment::create([
                'invoice_id' => $invoice->invoice_id,
                'type' => InvoiceAdjustment::TYPE_TERMINATION_FEE,
                'amount' => 0,
                'reason' => 'Future invoice refund due to early admission termination',
            ]);
        }
    }

    public function createRefundFull(Invoice $invoice, string $reason)
    {
        $refundableAmount = $this->getRefundableAmount($invoice);

        if ($refundableAmount <= 0) {
            return;
        }

        $existingAdjustment = InvoiceAdjustment::query()
            ->where('invoice_id', $invoice->invoice_id)
            ->where(
                'type',
                InvoiceAdjustment::TYPE_TERMINATION_FEE
            )
            ->exists();

        if (!$existingAdjustment) {
            InvoiceAdjustment::create([
                'invoice_id' => $invoice->invoice_id,
                'type' => InvoiceAdjustment::TYPE_TERMINATION_FEE,
                'amount' => 0,
                'reason' => $reason,
            ]);
        }

        $this->createRefundsForInvoice($invoice,  $refundableAmount,   $reason);
    }

    public function createRefundsForInvoice(
        Invoice $invoice,
        float $amount,
        string $reason,
        string $status = Refund::STATUS_PROCESSING,
        ?string $method = null,
        ?string $accountDetails = null
    ) {
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

        $invoice->loadMissing('payments.refunds');

        $remainingAmount = $amount;

        foreach ($invoice->payments as $payment) {
            if ($remainingAmount <= 0) {
                break;
            }

            $alreadyRefunded = (float) $payment->refunds
                ->whereIn('status', [
                    Refund::STATUS_COMPLETED,
                    Refund::STATUS_PROCESSING,
                ])
                ->sum('amount');

            $paymentAmount = (float) $payment->amount;

            $paymentRefundable = max(0,  $paymentAmount - $alreadyRefunded);

            if ($paymentRefundable <= 0) {
                continue;
            }

            $refundAmount = round(min($remainingAmount, $paymentRefundable),   2);

            if ($refundAmount <= 0) {
                continue;
            }

            Refund::create([
                'payment_id' => $payment->payment_id,
                'amount' => $refundAmount,
                'refund_method' => $method ?? $payment->payment_method,
                'status' => $status,
                'reason' => $reason,
                'masked_card_number' => $accountDetails
                    ?? $payment->masked_card_number,
            ]);

            $remainingAmount = round(
                $remainingAmount - $refundAmount,
                2
            );
        }

        if ($remainingAmount > 0) {
            throw new Exception('Unable to process the requested refund amount.',  422);
        }
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

        $amount = $this->getRefundableAmount($invoice);

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
                $payload['reason'] ?? 'Refund requested by the patient\'s family.',
                Refund::STATUS_PENDING,
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
                $payload['reason'] ?? 'Refund issued by accounting.',
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

    public function getTerminationFeeAmount(PatientAdmission $admission, mixed $contract)
    {
        if (!$contract) {
            return 0;
        }

        if (!$this->isWithinTerminationFeeWindow($admission)) {
            return 0;
        }

        $price = $this->getContractPrice($contract);

        return round(
            $price * $this->terminationFeeRate($contract->branch_id),
            2
        );
    }

    public function getDischargeCalculation(Invoice $invoice,  PatientAdmission $admission,  InvoiceAccommodation $invoiceAccommodation)
    {
        $contract = $invoiceAccommodation->branchContract;

        $admissionDate = $admission->admitted_at
            ? Carbon::parse($admission->admitted_at)
            : null;

        $dischargeDate = $admission->end_date
            ? Carbon::parse($admission->end_date)
            : (
                $admission->discharge_date
                ? Carbon::parse($admission->discharge_date)
                : null
            );

        $paid = $this->getNetPaidAmount($invoice);

        if (!$contract) {
            return $this->emptyDischargeCalculation($admission, $admissionDate,  $dischargeDate, $paid);
        }

        $billingCycle = $this->getBillingCycle($contract);
        $contractPrice = $this->getContractPrice($contract);
        $days = $this->calculateAdmissionDays($admissionDate);

        $withinTerminationWindow = $days !== null && $days < self::TERMINATION_FEE_WINDOW_DAYS;
        $withinYearlyHalfWindow = $days !== null && $billingCycle === 'YEARLY' && $days >= self::TERMINATION_FEE_WINDOW_DAYS && $days < self::YEARLY_HALF_REFUND_WINDOW_DAYS;

        $daysStayedAmount = 0;

        if ($withinTerminationWindow) {
            $rate = $this->terminationFeeRate($invoice->branch_id);
            $feeBaseAmount = $paid;
            $terminationFeePercent = round($rate * 100);
            $terminationFeeAmount = round($paid * $rate, 2);
            $refundAmount = round(max(0, $paid - $terminationFeeAmount), 2);
        } elseif ($withinYearlyHalfWindow) {
            $feeBaseAmount = (float) $invoiceAccommodation->price;
            $half = round($feeBaseAmount / 2, 2);
            $daysStayedAmount = round(($days / 365) * $feeBaseAmount, 2);
            $refundAmount = round(max(0, min($paid, $half - $daysStayedAmount)), 2);
            $terminationFeePercent = 50;
            $terminationFeeAmount = round($paid - $refundAmount, 2);
        } else {
            $feeBaseAmount = $paid;
            $terminationFeePercent = 100;
            $terminationFeeAmount = $paid;
            $refundAmount = 0;
        }

        $requiredPayment = round($paid - $refundAmount, 2);
        $eligibleForRefund = $refundAmount > 0;

        [$policy, $policyTitle, $policyDescription] = $this->getDischargePolicyText(
            $withinTerminationWindow,
            $withinYearlyHalfWindow,
            $eligibleForRefund,
            $billingCycle,
            $terminationFeePercent
        );

        return [
            'admission_id' => $admission->patient_admission_id,
            'eligible_for_refund' => $eligibleForRefund,
            'billing_cycle' => $billingCycle,
            'admission_date' => $admissionDate?->toIso8601String(),
            'discharge_date' => $dischargeDate?->toIso8601String(),
            'days_since_admission' => $days,
            'contract_price' => $contractPrice,
            'amount_paid' => round($paid, 2),
            'required_payment' => round($requiredPayment, 2),
            'fee_base_amount' => round($feeBaseAmount, 2),
            'days_stayed_amount' => round($daysStayedAmount, 2),
            'retention_percent' => $terminationFeePercent,
            'retention_amount' => round($terminationFeeAmount, 2),
            'termination_fee_percent' => $terminationFeePercent,
            'termination_fee_amount' => round($terminationFeeAmount,  2),
            'refund_amount' => round($refundAmount, 2),
            'policy' => $policy,
            'policy_title' => $policyTitle,
            'policy_description' => $policyDescription,
            'is_within_termination_fee_window' => $withinTerminationWindow,
            'is_within_yearly_half_refund_window' => $withinYearlyHalfWindow,
            'is_under_required_payment' => $paid < $requiredPayment,
            'payment_shortfall' => round(max(0, $requiredPayment - $paid), 2),
        ];
    }


    private function getDischargePolicyText(
        bool $withinTerminationWindow,
        bool $withinYearlyHalfWindow,
        bool $eligibleForRefund,
        string $billingCycle,
        int $terminationFeePercent
    ): array {
        if ($withinTerminationWindow) {
            return [
                $eligibleForRefund ? 'Refund available' : 'Payment required',
                '7-day termination policy',
                "Discharged within 7 days of admission. The payment is refunded less the {$terminationFeePercent}% termination fee.",
            ];
        }

        if ($withinYearlyHalfWindow) {
            return [
                $eligibleForRefund ? 'Refund available' : 'No refund',
                'Yearly refund policy',
                'Discharged after 7 days and before 6 months. Half of the yearly payment is refunded, half is retained.',
            ];
        }

        if ($billingCycle === 'MONTHLY') {
            return [
                'No refund',
                'Outside refund window',
                'The 7-day monthly refund window has passed. No refund applies.',
            ];
        }

        if ($billingCycle === 'YEARLY') {
            return [
                'No refund',
                'Outside refund window',
                'The 6-month yearly refund window has passed. No refund applies.',
            ];
        }

        return [
            'No refund',
            'Outside refund window',
            'No refund applies.',
        ];
    }

    protected function isWithinTerminationFeeWindow(PatientAdmission $admission)
    {
        $days = $this->calculateAdmissionDays(
            $admission->admitted_at
                ? Carbon::parse($admission->admitted_at)
                : null
        );

        return $days !== null
            && $days < self::TERMINATION_FEE_WINDOW_DAYS;
    }

    protected function isWithinYearlyHalfRefundWindow(PatientAdmission $admission)
    {
        $days = $this->calculateAdmissionDays(
            $admission->admitted_at
                ? Carbon::parse($admission->admitted_at)
                : null
        );

        return $days !== null
            && $days >= self::TERMINATION_FEE_WINDOW_DAYS
            && $days < self::YEARLY_HALF_REFUND_WINDOW_DAYS;
    }

    private function calculateAdmissionDays(?Carbon $admissionDate)
    {
        if (!$admissionDate) {
            return null;
        }

        $now = now();

        if ($now->isBefore($admissionDate)) {
            return 0;
        }

        return $admissionDate
            ->copy()
            ->startOfDay()
            ->diffInDays(
                $now->copy()->startOfDay()
            );
    }

    private function getContractPrice(mixed $contract)
    {
        return round((float) ($contract->price ?? 0), 2);
    }

    private function getBillingCycle(mixed $contract)
    {
        return strtoupper(trim($contract->billing_cycle ?? ''));
    }

    private function emptyDischargeCalculation(PatientAdmission $admission, ?Carbon $admissionDate, ?Carbon $dischargeDate,  float $paid)
    {
        return [
            'admission_id' => $admission->patient_admission_id,
            'eligible_for_refund' => false,
            'billing_cycle' => null,
            'admission_date' => $admissionDate?->toIso8601String(),
            'discharge_date' => $dischargeDate?->toIso8601String(),
            'days_since_admission' => $this->calculateAdmissionDays($admissionDate),
            'contract_price' => 0,
            'amount_paid' => round($paid, 2),
            'required_payment' => 0,
            'fee_base_amount' => 0,
            'days_stayed_amount' => 0,
            'retention_percent' => 0,
            'retention_amount' => 0,
            'termination_fee_percent' => 0,
            'termination_fee_amount' => 0,
            'refund_amount' => 0,
            'policy' => 'No refund',
            'policy_title' => 'Outside refund window',
            'policy_description' => 'No refund applies.',
            'is_within_termination_fee_window' => false,
            'is_within_yearly_half_refund_window' => false,
            'is_under_required_payment' => false,
            'payment_shortfall' => 0,
        ];
    }
}
