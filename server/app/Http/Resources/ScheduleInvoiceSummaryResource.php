<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleInvoiceSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'schedule'       => $this->resource['schedule'],
            'patient'        => $this->resource['patient'],
            'total_amount'   => $this->resource['total_amount'],
            'total_paid'     => $this->resource['total_paid'],
            'total_balance'  => $this->resource['total_balance'],
            'status'         => $this->resource['status'],
            'invoice_count'  => $this->resource['invoice_count'],
            'latest_invoice' => $this->resource['latest_invoice'],
            'invoices'       => $this->resource['invoices'],
        ];
    }
}
