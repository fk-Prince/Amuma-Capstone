<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientInvoiceSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $summary = [
            'patient' => $this->resource['patient'],
            'total_amount' => $this->resource['total_amount'],
            'total_paid' => $this->resource['total_paid'],
            'total_refunded' => $this->resource['total_refunded'],
            'total_withdrawn' => $this->resource['total_withdrawn'] ?? 0,
            'total_refund_requested' =>  $this->resource['total_refund_requested'],
            'total_refundable' => $this->resource['total_refundable'] ?? 0,
            'refund_status' =>   $this->resource['refund_status'],
            'total_balance' => $this->resource['total_balance'],
            'total_written_off' => $this->resource['total_written_off'] ?? 0,
            'status' => $this->resource['status'],
            'invoice_count' => $this->resource['invoice_count'],
            'latest_invoice' =>  $this->resource['latest_invoice'],
        ];

        // A section the caller did not ask for is absent rather than empty, so
        // the client keeps whatever it already loaded for that tab.
        foreach ([
            'invoices',
            'voided_invoices',
            'payments',
            'refunds',
            'admissions',
            'services',
            'admission_invoices',
            'service_invoices',
            'discharge_calculation',
        ] as $section) {
            if (array_key_exists($section, $this->resource)) {
                $summary[$section] = $this->resource[$section];
            }
        }

        return $summary;
    }
}
