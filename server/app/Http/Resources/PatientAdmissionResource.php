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

        $currentInvoice = $this->currentInvoiceAdmission;

        $invoiceAdmissionLiness = $this->relationLoaded('invoiceAdmission')
            ? $this->invoiceAdmission
            : collect();

        $allFacilities = $invoiceAdmissionLiness;

        if ($currentInvoice && !$allFacilities->contains(
            fn($f) => $f->invoice_admission_id === $currentInvoice->invoice_admission_id
        )) {
            $allFacilities = $allFacilities->push($currentInvoice);
        }

        $invoices = $allFacilities->map->invoice->filter();

        $totalAmount   = (float) $invoices->sum('total_amount');
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
            'current_invoice'  => $this->formatInvoiceAdmission($currentInvoice),

            'total_amount'   => $totalAmount,
            'total_paid'     => $totalPaid,
            'total_refunded' => $totalRefunded,
            'total_balance'  => $totalBalance,

            'status_summary' => match (true) {
                $totalBalance <= 0 && $totalPaid > 0 => 'Paid',
                $totalPaid > 0     => 'Partial',
                default             => 'Pending',
            },

            'invoices' => $invoiceAdmissionLiness
                ->map(fn($f) => $this->formatInvoiceAdmission($f))
                ->values(),
        ];
    }

    private function formatInvoiceAdmission(mixed $invoiceAdmissionLines): ?array
    {
        if (!$invoiceAdmissionLines) {
            return null;
        }

        $invoice = $invoiceAdmissionLines->invoice;

        return [
            'invoice_admission_id' => $invoiceAdmissionLines->invoice_admission_id,
            'invoice_id'          => $invoiceAdmissionLines->invoice_id,
            'invoice_code'        => $invoice?->invoice_code,
            'status'              => $invoice?->status,
            'price'               => $invoice?->total_amount,

            'paid_amount'     => $invoice?->amount_paid ?? 0,
            'refunded_amount' => $invoice?->refunded_amount ?? 0,
            'net_paid_amount' => $invoice?->net_paid_amount ?? 0,

            'accommodation_status' => $invoiceAdmissionLines->status,

            'contract' => new BranchContractResource($invoiceAdmissionLines->branchContract),
        ];
    }
}
