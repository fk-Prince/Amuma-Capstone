<?php

namespace App\Repository;

use App\Models\Transaction;

class TransactionRepository
{
    public function create(array $payload)
    {
        return Transaction::create($payload);
    }

    public function findByField(array $conditions)
    {
        return Transaction::where($conditions)->first();
    }

    public function findBookingPayment(string $transactionCode)
    {
        return Transaction::with('booking', 'branch.location', 'patient')
            ->where('transaction_code', $transactionCode)
            ->where('type', Transaction::TYPE_PAYMENT)
            ->whereNotNull('booking_id')
            ->first();
    }

    public function paymentsForBookings(array $bookingIds)
    {
        return Transaction::with('booking', 'branch.location', 'patient', 'payment.issuedBy')
            ->whereIn('booking_id', $bookingIds)
            ->where('type', Transaction::TYPE_PAYMENT)
            ->get()
            ->keyBy('booking_id');
    }

    public function codesForBookings(array $bookingIds)
    {
        return Transaction::whereIn('booking_id', $bookingIds)
            ->where('type', Transaction::TYPE_PAYMENT)
            ->pluck('transaction_code', 'booking_id');
    }

    public function forPatient(mixed $patientId)
    {
        return Transaction::where('patient_id', $patientId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function forClient(mixed $clientId)
    {
        return Transaction::with('branch:branch_id,name')
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function forBranchBetween(mixed $branchId, mixed $from, mixed $to)
    {
        return Transaction::where('branch_id', $branchId)
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at')
            ->get();
    }
}
