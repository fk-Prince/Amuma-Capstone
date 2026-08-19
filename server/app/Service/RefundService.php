<?php

namespace App\Service;

use App\Models\Invoice;
use App\Models\InvoiceAdjustment;
use App\Models\InvoiceFacility;
use App\Models\PatientAdmission;
use App\Models\Refund;
use Carbon\Carbon;
use Exception;

class RefundService
{
    private const TERMINATION_FEE_WINDOW_DAYS = 7;
    private const YEARLY_HALF_REFUND_WINDOW_DAYS = 183;
    private const YEARLY_HALF_REFUND_RATE = 0.5;
    private const TERMINATION_FEE_RATE = 0.2;

    public function __construct() {}

    public function getPaidAmount(Invoice $invoice)
    {
        return round((float) $invoice->payments()->sum('amount'), 2);
    }

    public function getRefundedAmount(Invoice $invoice)
    {
        return round(
            (float) $invoice->payments()
                ->with('refunds')
                ->get()
                ->flatMap(fn($payment) => $payment->refunds)
                ->whereIn('status', [Refund::STATUS_COMPLETED, Refund::STATUS_PROCESSING])
                ->sum('amount'),
            2
        );
    }

    public function getNetPaidAmount(Invoice $invoice)
    {
        return round(max(0, $this->getPaidAmount($invoice) - $this->getRefundedAmount($invoice)), 2);
    }

    public function getRefundableAmount(Invoice $invoice)
    {
        return round($this->getNetPaidAmount($invoice), 2);
    }

    public function getRequiredPaymentAmount(InvoiceFacility $invoiceFacility,  PatientAdmission $admission)
    {
        $contract = $invoiceFacility->branchContract;

        if (!$contract)  return 0;

        $contractPrice = round((float) ($contract->price ?? 0),   2);

        if ($contractPrice <= 0) return 0;

        $billingCycle = strtoupper(trim($contract->billing_cycle ?? ''));

        if ($this->isWithinTerminationFeeWindow($admission)) {
            return round($contractPrice * self::TERMINATION_FEE_RATE, 2);
        }

        if ($billingCycle === 'YEARLY') {
            if ($this->isWithinYearlyHalfRefundWindow($admission)) {
                return round($contractPrice * self::YEARLY_HALF_REFUND_RATE,  2);
            }
            return $contractPrice;
        }

        if ($billingCycle === 'MONTHLY') {
            return $contractPrice;
        }

        return 0;
    }

    public function validateRequiredPayment(Invoice $invoice,  InvoiceFacility $invoiceFacility, PatientAdmission $admission)
    {
        $netPaidAmount = $this->getNetPaidAmount($invoice);

        $requiredPayment = $this->getRequiredPaymentAmount($invoiceFacility, $admission);

        if ($requiredPayment <= 0) {
            return;
        }

        if ($netPaidAmount < $requiredPayment) {
            $shortfall = round($requiredPayment - $netPaidAmount, 2);
            throw new Exception("Required payment has not been met. Paid: {$netPaidAmount}, Required: {$requiredPayment}, Short by: {$shortfall}.", 422);
        }
    }

    public function hasRequiredPayment(Invoice $invoice,    InvoiceFacility $invoiceFacility, PatientAdmission $admission)
    {
        $netPaidAmount = $this->getNetPaidAmount($invoice);
        $requiredPayment = $this->getRequiredPaymentAmount($invoiceFacility,   $admission);
        return $netPaidAmount >= $requiredPayment;
    }

    public function createRefundCurrentInvoice(Invoice $invoice,   PatientAdmission $admission, InvoiceFacility $invoiceFacility)
    {
        $contract = $invoiceFacility->branchContract;

        if (!$contract)  return;

        $billingCycle = strtoupper(trim($contract->billing_cycle ?? ''));

        if (!in_array($billingCycle,  ['YEARLY', 'MONTHLY'],  true)) return;

        $netPaidAmount = $this->getNetPaidAmount($invoice);

        if ($netPaidAmount <= 0)  return;

        $requiredPayment = $this->getRequiredPaymentAmount($invoiceFacility, $admission);

        if ($netPaidAmount < $requiredPayment) {
            $shortfall = round($requiredPayment - $netPaidAmount, 2);
            throw new Exception("Required payment has not been met. Paid: {$netPaidAmount}, Required: {$requiredPayment}, Short by: {$shortfall}.", 422);
        }

        $refundAmount = round(max(0, $netPaidAmount - $requiredPayment), 2);

        if ($refundAmount <= 0)  return;

        InvoiceAdjustment::create([
            'invoice_id' => $invoice->invoice_id,
            'type' => InvoiceAdjustment::TYPE_REFUND,
            'amount' => $refundAmount,
            'reason' => 'Refund due to early admission termination',
        ]);

        $this->createRefundsForInvoice($invoice,  $refundAmount, 'Refund processed due to early admission termination.');
    }

    public function createRefundFutureInvoice(Invoice $invoice, array $payload)
    {
        if (empty($payload['refund'])) return;

        $refundableAmount = $this->getRefundableAmount($invoice);

        if ($refundableAmount <= 0)  return;

        $this->createRefundsForInvoice($invoice,  $refundableAmount, 'Future invoice refunded due to admission discharge.');

        InvoiceAdjustment::create([
            'invoice_id' => $invoice->invoice_id,
            'type' => InvoiceAdjustment::TYPE_REFUND,
            'amount' => $refundableAmount,
            'reason' => 'Future invoice refund due to early admission termination',
        ]);
    }

    public function createRefundFull(Invoice $invoice,  string $reason)
    {
        $refundableAmount = $this->getRefundableAmount($invoice);

        if ($refundableAmount <= 0)  return;

        InvoiceAdjustment::create([
            'invoice_id' => $invoice->invoice_id,
            'type' => InvoiceAdjustment::TYPE_REFUND,
            'amount' => $refundableAmount,
            'reason' => $reason,
        ]);

        $this->createRefundsForInvoice($invoice, $refundableAmount,   $reason);
    }

    public function createRefundsForInvoice(Invoice $invoice,    float $amount, string $reason)
    {
        $amount = round($amount, 2);

        if ($amount <= 0)   return;

        $refundableAmount = $this->getRefundableAmount($invoice);

        if ($amount > $refundableAmount) {
            throw new Exception('Refund amount exceeds the refundable amount.',  422);
        }

        $invoice->loadMissing('payments.refunds');

        $remainingAmount = $amount;

        foreach ($invoice->payments as $payment) {
            if ($remainingAmount <= 0)  break;

            $alreadyRefunded = (float) $payment->refunds
                ->whereIn('status', [
                    Refund::STATUS_COMPLETED,
                    Refund::STATUS_PROCESSING,
                ])
                ->sum('amount');

            $paymentAmount = (float) $payment->amount;

            $paymentRefundable = max(0,  $paymentAmount - $alreadyRefunded);

            if ($paymentRefundable <= 0)  continue;

            $refundAmount = round(min($remainingAmount,    $paymentRefundable), 2);

            if ($refundAmount <= 0) continue;

            Refund::create([
                'payment_id' => $payment->payment_id,
                'amount' => $refundAmount,
                'refund_method' => $payment->payment_method,
                'reference_id' => null,
                'status' => Refund::STATUS_PROCESSING,
                'reason' => $reason,
            ]);

            $remainingAmount = round($remainingAmount - $refundAmount, 2);
        }

        if ($remainingAmount > 0) throw new Exception('Unable to process the requested refund amount.', 422);
    }

    public function getTerminationFeeAmount(PatientAdmission $admission, mixed $contract)
    {
        if (!$this->isWithinTerminationFeeWindow($admission)) {
            return 0;
        }

        $contractPrice = round((float) ($contract->price ?? 0), 2);

        if ($contractPrice <= 0) {
            return 0;
        }

        return round($contractPrice * self::TERMINATION_FEE_RATE, 2);
    }

    protected function isWithinTerminationFeeWindow(PatientAdmission $admission)
    {
        $admittedAt = Carbon::parse($admission->admitted_at);

        if ($admittedAt->isFuture()) {
            return false;
        }

        return $admittedAt->diffInDays(now()) < self::TERMINATION_FEE_WINDOW_DAYS;
    }


    protected function isWithinYearlyHalfRefundWindow(PatientAdmission $admission)
    {
        $admittedAt = Carbon::parse($admission->admitted_at);

        if ($admittedAt->isFuture()) {
            return false;
        }

        $daysSinceStart = $admittedAt->diffInDays(now());

        return $daysSinceStart >= self::TERMINATION_FEE_WINDOW_DAYS && $daysSinceStart < self::YEARLY_HALF_REFUND_WINDOW_DAYS;
    }


    // public function cancelRefund(array $payload)
    // {
    //     return DB::transaction(function () use ($payload) {
    //         $refund = $this->refundRepository->findRefund($payload);

    //         if (!$refund) {
    //             throw new Exception(
    //                 'Refund not found.',
    //                 404
    //             );
    //         }

    //         if ($refund->status !== Refund::STATUS_PROCESSING) {
    //             throw new Exception(
    //                 'This refund cannot be cancelled.',
    //                 400
    //             );
    //         }

    //         $refund->update([
    //             'status' => Refund::STATUS_CANCELLED,
    //         ]);

    //         $refund->load('payment.invoice');

    //         $invoice = $refund->payment?->invoice;

    //         if ($invoice) {
    //             $paid = $this->getPaidAmount($invoice);
    //             $refunded = $this->getRefundedAmount($invoice);

    //             $netPaid = max(
    //                 0,
    //                 $paid - $refunded
    //             );

    //             if ($netPaid <= 0) {
    //                 $invoice->update([
    //                     'status' => Invoice::STATUS_PENDING,
    //                 ]);
    //             } elseif ($netPaid < (float) $invoice->total) {
    //                 $invoice->update([
    //                     'status' => Invoice::STATUS_PARTIAL,
    //                 ]);
    //             } else {
    //                 $invoice->update([
    //                     'status' => Invoice::STATUS_PAID,
    //                 ]);
    //             }
    //         }

    //         return [
    //             'message' => 'Refund has been cancelled successfully.',
    //             'refund' => $refund->fresh(),
    //             'invoice' => $invoice?->fresh(),
    //         ];
    //     });
    // }


    public function getDischargeCalculation(Invoice $invoice, PatientAdmission $admission, InvoiceFacility $invoiceFacility)
    {
        $contract = $invoiceFacility->branchContract;

        $admissionDate = $admission->admitted_at
            ? Carbon::parse($admission->admitted_at)
            : null;

        $dischargeDate = $admission->end_date
            ? Carbon::parse($admission->end_date)
            : ($admission->discharge_date
                ? Carbon::parse($admission->discharge_date)
                : null);

        $netPaidAmount = $this->getNetPaidAmount($invoice);

        if (!$contract) {
            return [
                'eligible_for_refund' => false,
                'billing_cycle' => null,
                'admission_date' => $admissionDate?->toIso8601String(),
                'discharge_date' => $dischargeDate?->toIso8601String(),
                'days_since_admission' => $this->calculateAdmissionDays($admissionDate),
                'contract_price' => 0,
                'amount_paid' => $netPaidAmount,
                'required_payment' => 0,
                'retention_percent' => 0,
                'retention_amount' => 0,
                'termination_fee_percent' => 0,
                'termination_fee_amount' => 0,
                'refund_amount' => 0,
                'is_within_termination_fee_window' => false,
                'is_within_yearly_half_refund_window' => false,
                'is_under_required_payment' => false,
                'payment_shortfall' => 0,
            ];
        }

        $billingCycle = strtoupper(trim($contract->billing_cycle ?? ''));
        $contractPrice = round((float) ($contract->price ?? 0), 2);
        $daysSinceAdmission = $this->calculateAdmissionDays($admissionDate);
        $withinTerminationFeeWindow = $daysSinceAdmission !== null  && $daysSinceAdmission < self::TERMINATION_FEE_WINDOW_DAYS;
        $withinYearlyHalfRefundWindow = $daysSinceAdmission !== null  && $daysSinceAdmission >= self::TERMINATION_FEE_WINDOW_DAYS  && $daysSinceAdmission < self::YEARLY_HALF_REFUND_WINDOW_DAYS;
        $requiredPayment = $this->getRequiredPaymentAmount($invoiceFacility, $admission);
        $retentionAmount = 0;
        $terminationFeeAmount = 0;
        $retentionPercent = 20; // change this
        $refundAmount = 0;
        $eligibleForRefund = false;
        if ($withinTerminationFeeWindow) {
            $eligibleForRefund = true;
            $terminationFeeAmount = round($contractPrice * self::TERMINATION_FEE_RATE, 2);
            $retentionAmount = $terminationFeeAmount;
            $refundAmount = round(max(0, $netPaidAmount - $retentionAmount), 2);
        } elseif ($billingCycle === 'YEARLY' && $withinYearlyHalfRefundWindow) {
            $retentionPercent = 50;
            $eligibleForRefund = true;
            $retentionAmount = round($contractPrice * self::YEARLY_HALF_REFUND_RATE,   2);
            $refundAmount = round(max(0, $netPaidAmount - $retentionAmount),   2);
        } elseif ($billingCycle === 'YEARLY') {
            $retentionAmount = $contractPrice;
            $retentionPercent = 100;
        } elseif ($billingCycle === 'MONTHLY') {
            $retentionPercent = 100;
            $retentionAmount = $contractPrice;
        }

        return [
            'admission_id' => $admission->patient_admission_id,
            'eligible_for_refund' => $eligibleForRefund,
            'billing_cycle' => $billingCycle,
            'admission_date' => $admissionDate?->toIso8601String(),
            'discharge_date' => $dischargeDate?->toIso8601String(),
            'days_since_admission' => $daysSinceAdmission,
            'contract_price' => $contractPrice,
            'amount_paid' => round($netPaidAmount, 2),
            'required_payment' => round($requiredPayment, 2),
            'retention_amount' => round($retentionAmount, 2),
            'termination_fee_percent' => $retentionPercent,
            'termination_fee_amount' => round($terminationFeeAmount, 2),
            'refund_amount' => round($refundAmount, 2),
            'is_within_termination_fee_window' => $withinTerminationFeeWindow,
            'is_within_yearly_half_refund_window' => $withinYearlyHalfRefundWindow,
            'is_under_required_payment' => $netPaidAmount < $requiredPayment,
            'payment_shortfall' => max(round($requiredPayment - $netPaidAmount, 2),  0),
        ];
    }

    private function calculateAdmissionDays(Carbon $admissionDate,)
    {
        if (!$admissionDate) {
            return null;
        }

        $endDate =  now();

        if ($endDate->isBefore($admissionDate)) {
            return 0;
        }

        return $admissionDate
            ->copy()
            ->startOfDay()
            ->diffInDays(
                $endDate
                    ->copy()
                    ->startOfDay()
            );
    }
}
