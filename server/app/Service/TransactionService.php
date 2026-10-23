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

    public function bookingReceipt(string $transactionCode): ?Transaction
    {
        return $this->transactionRepository->findBookingPayment($transactionCode);
    }

    public function paginateForBranch(mixed $branchId, string $search, int $perPage, ?string $type = null)
    {
        return $this->transactionRepository->paginateForBranch($branchId, $search, $perPage, $type);
    }

    public function paymentsForBookings(array $bookingIds)
    {
        return $this->transactionRepository->paymentsForBookings($bookingIds);
    }

    public function bookingPayment(mixed $bookingId): ?Transaction
    {
        if (!$bookingId) {
            return null;
        }

        return $this->transactionRepository->findByField([
            ['booking_id', '=', $bookingId],
            ['type', '=', Transaction::TYPE_PAYMENT],
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

    public function forRefund(
        float $amount,
        mixed $branchId,
        mixed $patientId,
        ?string $description = null,
        array $extra = []
    ): Transaction {
        return $this->record(
            Transaction::TYPE_REFUND,
            Transaction::DIRECTION_DEBIT,
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
