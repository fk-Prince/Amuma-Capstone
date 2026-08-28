<?php

namespace App\Repository;

use App\Models\Refund;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RefundRepository
{
    public function findRefund(array $payload)
    {
        if (!empty($payload['refund_id'])) {
            return Refund::query()
                ->with('payment.invoice')
                ->find($payload['refund_id']);
        }

        if (!empty($payload['invoice_id'])) {
            return Refund::query()
                ->whereHas('payment', function ($query) use ($payload) {
                    $query->where('invoice_id', $payload['invoice_id']);
                })
                ->where('status', Refund::STATUS_PROCESSING)
                ->latest('created_at')
                ->get();
        }

        if (!empty($payload['invoice_code'])) {
            return Refund::query()
                ->whereHas('payment.invoice', function ($query) use ($payload) {
                    $query->where('invoice_code', $payload['invoice_code']);
                })
                ->where('status', Refund::STATUS_PROCESSING)
                ->latest('created_at')
                ->get();
        }

        $patientUuid = $payload['patient_uuid']
            ?? $payload['p_uuid']
            ?? null;

        if (!$patientUuid) {
            return collect();
        }

        return Refund::query()
            ->with('payment.invoice')
            ->whereHas(
                'payment.invoice.invoiceAccommodation.patientAdmission.patient',
                function ($query) use ($patientUuid) {
                    $query->where('uuid', $patientUuid);
                }
            )
            ->where('status', Refund::STATUS_PROCESSING)
            ->latest('created_at')
            ->get();
    }
}
