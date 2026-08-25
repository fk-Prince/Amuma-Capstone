<?php

namespace App\Service;

use App\Models\Invoice;
use App\Models\InvoiceAdjustment;
use App\Models\InvoiceFacility;
use App\Models\PatientAdmission;
use App\Models\Refund;
use App\Utils\MaskUtil;
use Carbon\Carbon;
use Exception;

class RefundService
{
    private const TERMINATION_FEE_WINDOW_DAYS = 7;
    private const TERMINATION_FEE_RATE = 0.20;

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

    public function getRefundableAmount(Invoice $invoice): float
    {
        return $this->getNetPaidAmount($invoice);
    }


    public function getCancellationRefundAmount(
        Invoice $invoice,
        PatientAdmission $admission,
        InvoiceFacility $invoiceFacility
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
            $retain = round($paid * self::TERMINATION_FEE_RATE, 2);

            return round(max(0, $paid - $retain), 2);
        }

        $contract = $invoiceFacility->branchContract;
        $billingCycle = $contract ? $this->getBillingCycle($contract) : '';

        if ($billingCycle === 'YEARLY' && $days < self::YEARLY_HALF_REFUND_WINDOW_DAYS) {
            $half = round($paid / 2, 2);

            $daysStayedAmount = round(
                ($days / 365) * (float) $invoiceFacility->price,
                2
            );

            return round(max(0, $half - $daysStayedAmount), 2);
        }

        return 0;
    }

    public function getRequiredPaymentAmount(InvoiceFacility $invoiceFacility,  PatientAdmission $admission)
    {
        $contract = $invoiceFacility->branchContract;

        if (!$contract) {
            return 0;
        }

        $price = $this->getContractPrice($contract);

        if ($price <= 0) {
            return 0;
        }

        $billingCycle = $this->getBillingCycle($contract);

        if ($this->isWithinTerminationFeeWindow($admission)) {
            return round($price * self::TERMINATION_FEE_RATE,    2);
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

    public function validateRequiredPayment(Invoice $invoice,  InvoiceFacility $invoiceFacility, PatientAdmission $admission)
    {
        $paid = $this->getNetPaidAmount($invoice);

        $required = $this->getRequiredPaymentAmount(
            $invoiceFacility,
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

    public function hasRequiredPayment(Invoice $invoice, InvoiceFacility $invoiceFacility, PatientAdmission $admission)
    {
        return $this->getNetPaidAmount($invoice) >= $this->getRequiredPaymentAmount($invoiceFacility, $admission);
    }

    public function createRefundCurrentInvoice(Invoice $invoice,    PatientAdmission $admission,  InvoiceFacility $invoiceFacility)
    {
        $calculation = $this->getDischargeCalculation(
            $invoice,
            $admission,
            $invoiceFacility
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
        $this->createRefundsForInvoice($invoice,  $refundAmount,  'Refund processed due to early admission termination.');
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

    public function createRefundsForInvoice(Invoice $invoice,   float $amount, string $reason)
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
                'refund_method' => $payment->payment_method,
                'status' => Refund::STATUS_PROCESSING,
                'reason' => $reason,
                'masked_card_number' => $payment->masked_card_number,
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


    public function claimPortalRefund(object $patient, array $payload): array
    {
        $method = trim((string) $payload['method']);
        $accountDetails = trim((string) $payload['account_details']);
        $maskedAccountDetails = MaskUtil::accountDetails($method, $accountDetails);

        $processingRefunds = $patient->patient_invoices
            ->flatMap(fn($invoice) => $invoice->payments)
            ->flatMap(fn($payment) => $payment->refunds)
            ->where('status', Refund::STATUS_PROCESSING);

        if ($processingRefunds->isEmpty()) {
            throw new Exception(
                'No pending refund is available to claim right now.',
                404
            );
        }

        foreach ($processingRefunds as $refund) {
            $refund->update([
                'refund_method' => $method,
                'masked_card_number' => $maskedAccountDetails ?? null,
                'status' => Refund::STATUS_COMPLETED,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Your refund has been claimed and marked complete.',
            'amount' => (float) $processingRefunds->sum('amount'),
        ];
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

        return round($price * self::TERMINATION_FEE_RATE, 2);
    }

    public function getDischargeCalculation(Invoice $invoice,  PatientAdmission $admission,  InvoiceFacility $invoiceFacility)
    {
        $contract = $invoiceFacility->branchContract;

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

        // Retention/refund is priced entirely off what was actually paid
        // (and, for the yearly mid-window tier, off invoiceFacility.price
        // rather than the branch contract's current price) — not off the
        // contract price, which may no longer reflect what this period was
        // actually invoiced for.
        $daysStayedAmount = 0;

        if ($withinTerminationWindow) {
            $feeBaseAmount = $paid;
            $terminationFeePercent = round(self::TERMINATION_FEE_RATE * 100);
            $terminationFeeAmount = round($paid * self::TERMINATION_FEE_RATE, 2);
            $refundAmount = round(max(0, $paid - $terminationFeeAmount), 2);
        } elseif ($withinYearlyHalfWindow) {
            $feeBaseAmount = (float) $invoiceFacility->price;
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
