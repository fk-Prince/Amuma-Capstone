<?php

namespace App\Repository;

use App\Models\Payment;

class PaymentRepository
{
    public function create(array $payload)
    {
        return Payment::create($payload);
    }

    public function findByField(array $conditions)
    {
        return Payment::where($conditions)->first();
    }

    public function findByCode(string $paymentCode, array $with = [])
    {
        return Payment::with($with)
            ->whereHas(
                'transaction',
                fn($query) => $query->where('transaction_code', $paymentCode)
            )
            ->first();
    }

    public const RECEIPT_RELATIONS = [
        'allocations.invoice.invoiceServices.scheduleService.service',
        'allocations.invoice.invoiceAdmissionLines.admissionPeriod.branchContract',
        'allocations.invoice.invoiceAdmissionLines.admissionPeriod.patientAdmission.bed.room',
        'transaction.branch.location',
        'transaction.patient',
        'transaction.client',
        'issuedBy',
    ];

    public function allocate(Payment $payment, array $payload)
    {
        return $payment->allocations()->create($payload + ['created_at' => now()]);
    }
}
