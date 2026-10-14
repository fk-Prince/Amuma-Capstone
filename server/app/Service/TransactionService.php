<?php

namespace App\Service;

use App\Models\Transaction;
use App\Repository\TransactionRepository;

class TransactionService
{
    public function __construct(
        private TransactionRepository $transactionRepository
    ) {}

    public function record(
        string $type,
        string $direction,
        float $amount,
        mixed $branchId,
        mixed $patientId = null,
        ?string $description = null,
        string $status = Transaction::STATUS_COMPLETED,
        array $extra = []
    ) {
        return $this->transactionRepository->create($extra + [
            'branch_id' => $branchId,
            'patient_id' => $patientId,
            'amount' => round(abs($amount), 2),
            'type' => $type,
            'direction' => $direction,
            'status' => $status,
            'description' => $description,
        ]);
    }

    public function settle(?Transaction $transaction, string $status, ?string $reason = null): void
    {
        $transaction?->update(array_filter([
            'status' => $status,
            'declined_reason' => $reason,
        ], fn($value) => $value !== null));
    }

    public function forPayment(
        float $amount,
        mixed $branchId,
        mixed $patientId,
        ?string $description = null,
        array $extra = []
    ): Transaction {
        return $this->record(
            Transaction::TYPE_PAYMENT,
            Transaction::DIRECTION_CREDIT,
            $amount,
            $branchId,
            $patientId,
            $description,
            Transaction::STATUS_COMPLETED,
            $extra
        );
    }

    public function forWithdraw(
        float $amount,
        mixed $branchId,
        mixed $patientId,
        ?string $description = null,
        string $status = Transaction::STATUS_COMPLETED,
        array $extra = []
    ) {
        return $this->record(
            Transaction::TYPE_WITHDRAW,
            Transaction::DIRECTION_DEBIT,
            $amount,
            $branchId,
            $patientId,
            $description,
            $status,
            $extra
        );
    }

}
