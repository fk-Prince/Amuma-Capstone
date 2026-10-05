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

    public function forPatient(mixed $patientId)
    {
        return Transaction::where('patient_id', $patientId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function paymentsByClient(mixed $clientId)
    {
        return Transaction::with('branch:branch_id,name')
            ->where('client_id', $clientId)
            ->where('type', Transaction::TYPE_PAYMENT)
            ->where('status', Transaction::STATUS_COMPLETED)
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
