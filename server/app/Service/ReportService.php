<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Resources\SalesVatResource;
use App\Models\Invoice;
use App\Models\User;
use App\Repository\ReportRepository;
use Carbon\Carbon;

class ReportService
{
    public function __construct(private ReportRepository $reportRepository) {}

    public function salesVat(array $payload, User $user)
    {
        $branch = $this->authorize($payload, $user, PermissionAction::Read);

        return SalesVatResource::collection(
            $this->reportRepository->paginateSales(
                $branch->branch_id,
                $this->filters($payload),
                (int) ($payload['per_page'] ?? 15),
            )
        );
    }

    public function salesVatSummary(array $payload, User $user): array
    {
        $branch = $this->authorize($payload, $user, PermissionAction::Read);
        $filters = $this->filters($payload);

        $invoices = $this->reportRepository->salesForPeriod($branch->branch_id, $filters['from'], $filters['to']);
        [$taxed, $exempt] = $invoices->partition(fn(Invoice $invoice) => (float) $invoice->vat_rate > 0);

        $vat = round((float) $taxed->sum('sales_vat'), 2);

        return [
            'gross_sales'      => round((float) $invoices->sum('sales_amount'), 2),
            'vatable_sales'    => round((float) $taxed->sum('sales_amount') - $vat, 2),
            'vat_exempt_sales' => round((float) $exempt->sum('sales_amount'), 2),
            'output_vat'       => $vat,
            'invoice_count'    => $invoices->count(),
        ];
    }

    public function salesVatExport(array $payload, User $user)
    {
        $branch = $this->authorize($payload, $user, PermissionAction::Export);

        return SalesVatResource::collection(
            $this->reportRepository->allSales($branch->branch_id, $this->filters($payload))
        );
    }

    private function authorize(array $payload, User $user, PermissionAction $action)
    {
        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Reports, $action);

        return $branch;
    }

    private function filters(array $payload): array
    {
        return [
            'from'   => Carbon::parse($payload['date_from'])->startOfDay(),
            'to'     => Carbon::parse($payload['date_to'])->endOfDay(),
            'status' => $payload['status'] ?? null,
            'search' => $payload['search'] ?? null,
        ];
    }
}
