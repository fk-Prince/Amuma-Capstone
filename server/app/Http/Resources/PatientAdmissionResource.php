<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientAdmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return [];
        }

        $currentInvoice = $this->currentInvoiceAccommodation;

        $invoiceAccommodations = $this->relationLoaded('invoiceAdmission')
            ? $this->invoiceAdmission
            : collect();

        $allFacilities = $invoiceAccommodations;

        if ($currentInvoice && !$allFacilities->contains(
            fn($f) => $f->invoice_accommodation_id === $currentInvoice->invoice_accommodation_id
        )) {
            $allFacilities = $allFacilities->push($currentInvoice);
        }

        $invoices = $allFacilities->map->invoice->filter();

        $totalAmount   = (float) $invoices->sum('total');
        $totalPaid     = (float) $invoices->sum(fn($inv) => $inv->net_paid_amount);
        $totalRefunded = (float) $invoices->sum(fn($inv) => $inv->refunded_amount);
        $totalBalance  = max($totalAmount - $totalPaid, 0);

        return [
            'patient_admission_id' => $this->patient_admission_id,
            'status'                => $this->status,
            'admitted_at'           => $this->admitted_at,
            'end_date'              => $this->end_date,

            'bed' => [
                'bed_id' => $this->bed?->bed_id,
                'bed_no' => $this->bed?->bed_no ?? 'N/A',
                'status' => $this->bed?->status,
            ],

            'room' => [
                'room_id'   => $this->bed?->room?->room_id,
                'room_no'   => $this->bed?->room?->room_no ?? 'N/A',
                'room_type' => $this->bed?->room?->room_type,
                'floor'     => $this->bed?->room?->floor,
            ],

            'current_contract' => new BranchContractResource($currentInvoice?->branchContract),
            'current_invoice'  => $this->formatInvoiceAccommodation($currentInvoice),

            'total_amount'   => $totalAmount,
            'total_paid'     => $totalPaid,
            'total_refunded' => $totalRefunded,
            'total_balance'  => $totalBalance,

            'status_summary' => match (true) {
                $totalBalance <= 0 && $totalPaid > 0 => 'Paid',
                $totalPaid > 0     => 'Partial',
                default             => 'Pending',
            },

            'invoices' => $invoiceAccommodations
                ->map(fn($f) => $this->formatInvoiceAccommodation($f))
                ->values(),
        ];
    }

    private function formatInvoiceAccommodation(mixed $invoiceAccommodation): ?array
    {
        if (!$invoiceAccommodation) {
            return null;
        }

        $invoice = $invoiceAccommodation->invoice;

        return [
            'invoice_accommodation_id' => $invoiceAccommodation->invoice_accommodation_id,
            'invoice_id'          => $invoiceAccommodation->invoice_id,
            'invoice_code'        => $invoice?->invoice_code,
            'status'              => $invoice?->status,
            'price'               => $invoice?->total,

            'paid_amount'     => $invoice?->amount_paid ?? 0,
            'refunded_amount' => $invoice?->refunded_amount ?? 0,
            'net_paid_amount' => $invoice?->net_paid_amount ?? 0,

            'start_date' => $invoiceAccommodation->start_date,
            'end_date'   => $invoiceAccommodation->end_date,

            'contract' => new BranchContractResource($invoiceAccommodation->branchContract),
        ];
    }
}
