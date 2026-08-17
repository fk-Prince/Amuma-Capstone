<?php

namespace App\Service;

use App\Models\Invoice;
use App\Models\Refund;
use Exception;

class RefundService
{
    public function getPaidAmount(Invoice $invoice): float
    {
        return (float) $invoice->payments->sum('amount');
    }

    public function getRefundedAmount(Invoice $invoice): float
    {
        return (float) $invoice->payments
            ->flatMap(fn($payment) => $payment->refunds)
            ->where('status', Refund::STATUS_COMPLETED)
            ->sum('amount');
    }

    public function getRefundableAmount(Invoice $invoice): float
    {
        return max(0, $this->getPaidAmount($invoice) - $this->getRefundedAmount($invoice));
    }

    public function createRefundsForInvoice(Invoice $invoice, float $amount, string $reason)
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            return;
        }

        $refundableAmount = $this->getRefundableAmount($invoice);

        if ($amount > $refundableAmount) {
            throw new Exception('Refund amount exceeds the refundable amount.');
        }

        $remainingAmount = $amount;

        foreach ($invoice->payments as $payment) {
            if ($remainingAmount <= 0) {
                break;
            }

            $alreadyRefunded = (float) $payment->refunds
                ->where('status', Refund::STATUS_COMPLETED)
                ->sum('amount');

            $paymentRefundable = max(0, (float) $payment->amount - $alreadyRefunded);

            if ($paymentRefundable <= 0) {
                continue;
            }

            $refundAmount = min($remainingAmount, $paymentRefundable);

            Refund::create([
                'payment_id'    => $payment->payment_id,
                'amount'        => round($refundAmount, 2),
                'refund_method' => $payment->payment_method,
                'reference_id'  => null,
                'status'        => Refund::STATUS_PROCESSING,
                'reason'        => $reason,
            ]);

            $remainingAmount = round($remainingAmount - $refundAmount, 2);
        }

        if ($remainingAmount > 0) {
            throw new Exception('Unable to process the requested refund amount.', 400);
        }
    }

    public function refundFull(Invoice $invoice, string $reason)
    {
        $amount = $this->getRefundableAmount($invoice);

        if ($amount <= 0) {
            return;
        }

        $this->createRefundsForInvoice($invoice, $amount, $reason);
    }

    public function refundFutureInvoice(Invoice $invoice, array $payload): void
    {
        $refundableAmount = $this->getRefundableAmount($invoice);

        if ($refundableAmount <= 0) {
            if ($this->getPaidAmount($invoice) <= 0) {
                $invoice->update(['status' => Invoice::STATUS_VOID]);
            }

            return;
        }

        if (empty($payload['refund'])) {
            return;
        }

        $this->refundFull($invoice, 'Invoice refunded due to admission discharge.');
    }

    public function refundCurrentInvoice(Invoice $invoice, array $payload): void
    {
        $refundableAmount = $this->getRefundableAmount($invoice);

        if ($refundableAmount <= 0) {
            if ($this->getPaidAmount($invoice) <= 0) {
                $invoice->update(['status' => Invoice::STATUS_VOID]);
            }

            return;
        }
        $requestedAmount = (float) ($payload['current_refund_amount'] ?? 0);

        if ($requestedAmount <= 0) {
            return;
        }

        $refundAmount = min($requestedAmount, $refundableAmount);

        $this->createRefundsForInvoice(
            $invoice,
            $refundAmount,
            'Invoice refunded due to admission discharge.'
        );

        $this->getRefundableAmount($invoice);
    }
}
