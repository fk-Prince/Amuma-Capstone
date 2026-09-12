<?php

namespace App\Repository;

use App\Models\Invoice;
use App\Models\Refund;
use App\Models\Transaction;

class RefundRepository
{
    public function create(float $amount, array $lines): Refund
    {
        $credit = Refund::create([
            'amount' => round($amount, 2),
            'created_at' => now(),
        ]);

        foreach ($lines as $line) {
            $credit->allocations()->create($line);
        }

        return $credit->load('allocations');
    }

    public function claim(Refund $credit, Transaction $transaction): Refund
    {
        $credit->update(['transaction_id' => $transaction->transaction_id]);

        return $credit;
    }

    public function split(Refund $credit, float $amount): Refund
    {
        $amount = round($amount, 2);
        $remainder = round((float) $credit->amount - $amount, 2);

        if ($remainder <= 0) {
            return $credit;
        }

        $taken = Refund::create([
            'amount' => $amount,
            'created_at' => $credit->created_at ?? now(),
        ]);

        $left = $amount;

        foreach ($credit->allocations()->orderBy('refund_allocation_id')->get() as $line) {
            if ($left <= 0) {
                break;
            }

            $share = round(min($left, (float) $line->amount), 2);

            $taken->allocations()->create([
                'allocation_id' => $line->allocation_id,
                'invoice_adjustment_id' => $line->invoice_adjustment_id,
                'amount' => $share,
            ]);

            $rest = round((float) $line->amount - $share, 2);

            $rest > 0
                ? $line->update(['amount' => $rest])
                : $line->delete();

            $left = round($left - $share, 2);
        }

        $credit->update(['amount' => $remainder]);

        return $taken->load('allocations');
    }

    public function availableFor(mixed $patientId)
    {
        return $this->forPatient($patientId)
            ->available()
            ->with('allocations.allocation.invoice')
            ->orderBy('refund_id')
            ->get();
    }

    public function creditFor(mixed $patientId): float
    {
        if (!$patientId) {
            return 0.0;
        }

        return round((float) $this->forPatient($patientId)->available()->sum('amount'), 2);
    }

    // Everything the bills handed back, whatever has since become of it. The
    // money left the invoices when the credit was raised, so a withdrawal that
    // is still waiting — or was turned down — does not change this.
    public function refundedCreditFor(mixed $patientId): float
    {
        if (!$patientId) {
            return 0.0;
        }

        return round((float) $this->forPatient($patientId)->sum('amount'), 2);
    }

    public function pendingCreditFor(mixed $patientId): float
    {
        return $this->withStatus($patientId, Transaction::STATUS_REQUESTED);
    }

    public function withdrawnCreditFor(mixed $patientId): float
    {
        return $this->withStatus($patientId, Transaction::STATUS_COMPLETED);
    }

    public function withdrawalsFor(mixed $patientId)
    {
        return Transaction::query()
            ->with('client', 'refunds.allocations.allocation.invoice')
            ->where('patient_id', $patientId)
            ->where('type', Transaction::TYPE_WITHDRAW)
            ->latest('created_at')
            ->get();
    }

    public function openWithdrawalFor(mixed $patientId): ?Transaction
    {
        return Transaction::query()
            ->where('patient_id', $patientId)
            ->where('type', Transaction::TYPE_WITHDRAW)
            ->where('status', Transaction::STATUS_REQUESTED)
            ->latest('created_at')
            ->first();
    }

    public function findWithdrawal(mixed $transactionId): ?Transaction
    {
        return Transaction::query()
            ->with('client', 'refunds.allocations.allocation.invoice')
            ->where('type', Transaction::TYPE_WITHDRAW)
            ->find($transactionId);
    }

    public function forInvoice(Invoice $invoice)
    {
        return Refund::query()
            ->with('transaction', 'allocations.invoiceAdjustment')
            ->whereHas(
                'allocations',
                fn($query) => $query->whereIn(
                    'allocation_id',
                    $invoice->allocations()->select('allocation_id')
                )
            )
            ->orderByDesc('refund_id')
            ->get();
    }

    private function forPatient(mixed $patientId)
    {
        return Refund::query()->forPatient($patientId);
    }

    private function withStatus(mixed $patientId, string $status): float
    {
        if (!$patientId) {
            return 0.0;
        }

        return round(
            (float) $this->forPatient($patientId)
                ->whereHas(
                    'transaction',
                    fn($query) => $query->where('type', Transaction::TYPE_WITHDRAW)
                        ->where('status', $status)
                )
                ->sum('amount'),
            2
        );
    }
}
