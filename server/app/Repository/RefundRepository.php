<?php

namespace App\Repository;

use App\Models\Invoice;
use App\Models\Refund;

class RefundRepository
{

    private function base()
    {
        return Refund::query()->with(
            'allocations.allocation.invoice',
            'allocations.allocation.payment'
        );
    }

    public function pendingForInvoice(Invoice $invoice)
    {
        return Refund::query()
            ->whereHas(
                'allocations',
                fn($query) => $query->whereIn(
                    'allocation_id',
                    $invoice->allocations()->select('allocation_id')
                )
            )
            ->where('status', Refund::STATUS_REQUESTED)
            ->get();
    }

    public function findRefund(array $payload)
    {
        if (!empty($payload['refund_id'])) {
            return $this->base()->find($payload['refund_id']);
        }

        if (!empty($payload['invoice_id'])) {
            return $this->requested(
                fn($query) => $query->where('invoice_id', $payload['invoice_id'])
            );
        }

        if (!empty($payload['invoice_code'])) {
            return $this->requested(
                fn($query) => $query->where('invoice_code', $payload['invoice_code'])
            );
        }

        $patientUuid = $payload['patient_uuid']
            ?? $payload['p_uuid']
            ?? null;

        if (!$patientUuid) {
            return collect();
        }

        return $this->base()
            ->whereHas(
                'allocations.allocation.invoice.invoiceAdmissionLines.admissionPeriod.patientAdmission.patient',
                fn($query) => $query->where('uuid', $patientUuid)
            )
            ->where('status', Refund::STATUS_REQUESTED)
            ->latest('created_at')
            ->get();
    }

    private function requested(callable $filter)
    {
        return $this->base()
            ->whereHas('allocations.allocation.invoice', $filter)
            ->where('status', Refund::STATUS_REQUESTED)
            ->latest('created_at')
            ->get();
    }
}
