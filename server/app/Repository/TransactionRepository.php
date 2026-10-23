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

    // Everything the branch has recorded, with what a payment's receipt needs.
    public function paginateForBranch(mixed $branchId, string $search, int $perPage, ?string $type = null)
    {
        return Transaction::with(array_merge(
            ['patient', 'client', 'booking', 'branch.location'],
            array_map(
                fn($relation) => 'payment.' . $relation,
                PaymentRepository::RECEIPT_RELATIONS
            )
        ))
            ->where('branch_id', $branchId)
            ->when($type, fn($query) => $query->where('type', $type))
            ->when($search !== '', function ($query) use ($search) {
                $term = '%' . $search . '%';

                $query->where(function ($q) use ($term, $search) {
                    $q->where('transaction_code', 'ilike', $term)
                        ->orWhere('party_name', 'ilike', $term)
                        ->orWhere('transaction_reference_id', 'ilike', $term)
                        ->orWhereHas(
                            'patient',
                            fn($p) => $p
                                ->whereRaw("concat(first_name, ' ', last_name) ilike ?", [$term])
                                ->orWhere('patient_code', 'ilike', "{$search}%")
                        )
                        ->orWhereHas(
                            'booking',
                            fn($b) => $b->where('reference_id', 'ilike', $term)
                        );
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('transaction_id')
            ->paginate($perPage);
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
