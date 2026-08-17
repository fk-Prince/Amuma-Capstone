<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientInvoiceSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'patient'                 => $this->resource['patient'],
            'total_amount'            => $this->resource['total_amount'],
            'total_paid'              => $this->resource['total_paid'],
            'total_refunded'          => $this->resource['total_refunded'],
            'total_refund_processing' => $this->resource['total_refund_processing'],
            'refund_status'           => $this->resource['refund_status'],
            'total_balance'           => $this->resource['total_balance'] - $this->resource['total_refund_processing'],
            'status'                  => $this->resource['status'],
            'invoice_count'           => $this->resource['invoice_count'],
            'latest_invoice'          => $this->resource['latest_invoice'],
            'invoices'                => $this->resource['invoices'],
        ];
    }
}
