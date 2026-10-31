<?php

namespace App\Repository;

use App\Models\Invoice;
use Carbon\Carbon;

class ReportRepository
{
    private const SALES_STATUSES = [
        Invoice::STATUS_PAID,
        Invoice::STATUS_PARTIAL,
        Invoice::STATUS_PENDING,
    ];

    public function paginateSales(int $branchId, array $filters, int $perPage)
    {
        return $this->salesQuery($branchId, $filters)
            ->with($this->salesRelations())
            ->orderByDesc('created_at')
            ->orderByDesc('invoice_id')
            ->paginate($perPage);
    }

    public function allSales(int $branchId, array $filters)
    {
        return $this->salesQuery($branchId, $filters)
            ->with($this->salesRelations())
            ->orderBy('created_at')
            ->orderBy('invoice_id')
            ->get();
    }

    public function salesForPeriod(int $branchId, Carbon $from, Carbon $to)
    {
        return Invoice::query()
            ->with('invoiceAdjustments', 'allocations.refundAllocations')
            ->where('branch_id', $branchId)
            ->where('status', '!=', Invoice::STATUS_VOID)
            ->whereBetween('created_at', [$from, $to])
            ->get(['invoice_id', 'status', 'total_amount', 'vat_rate', 'created_at']);
    }

    private function salesQuery(int $branchId, array $filters)
    {
        return Invoice::query()
            ->where('branch_id', $branchId)
            ->where('status', '!=', Invoice::STATUS_VOID)
            ->whereBetween('created_at', [$filters['from'], $filters['to']])
            ->when(
                in_array($filters['status'] ?? null, self::SALES_STATUSES, true),
                fn($query) => $query->where('status', $filters['status'])
            )
            ->when($filters['search'] ?? null, function ($query, $search) {
                $term = '%' . trim($search) . '%';
                $byName = fn($patient) => $patient->whereRaw("concat(first_name, ' ', last_name) ilike ?", [$term]);

                $query->where(function ($inner) use ($term, $byName) {
                    $inner->where('invoice_code', 'ilike', $term)
                        ->orWhereHas('invoiceAdmissionLines.admissionPeriod.patientAdmission.patient', $byName)
                        ->orWhereHas('invoiceServices.scheduleService.schedule.patient', $byName)
                        ->orWhereHas('additionalCharges.patient', $byName)
                        ->orWhereHas('diagnosisLines.admissionPeriod.patientAdmission.patient', $byName);
                });
            });
    }

    private function salesRelations(): array
    {
        return [
            'invoiceAdjustments',
            'allocations.refundAllocations',
            'invoiceAdmissionLines.admissionPeriod.patientAdmission.patient',
            'invoiceServices.scheduleService.schedule.patient',
            'additionalCharges.patient',
            'diagnosisLines.diagnosisCase',
            'diagnosisLines.admissionPeriod.patientAdmission.patient',
        ];
    }
}
