<?php

namespace App\Http\Controllers;

use App\Service\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function salesVat(Request $request)
    {
        return $this->reportService->salesVat($this->validated($request, true), $request->user());
    }

    public function salesVatSummary(Request $request)
    {
        return $this->reportService->salesVatSummary($this->validated($request), $request->user());
    }

    public function salesVatExport(Request $request)
    {
        return $this->reportService->salesVatExport($this->validated($request, true), $request->user());
    }

    private function validated(Request $request, bool $withFilters = false): array
    {
        return $request->validate([
            'branch_uuid' => ['required', 'string'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ] + ($withFilters ? [
            'status' => ['nullable', 'string', 'in:paid,partially_paid,unpaid'],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ] : []));
    }
}
