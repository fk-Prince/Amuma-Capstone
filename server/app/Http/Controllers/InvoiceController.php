<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Service\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    private InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function overview(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::BillingAndInvoices, PermissionAction::Read);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->invoiceService->overview($request->all());
    }

    public function store(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::BillingAndInvoices, PermissionAction::Create);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->invoiceService->storeBooking($request->user(), $request->all());
    }

    public function show(Request $request, string $uuid)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::BillingAndInvoices, PermissionAction::Read);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->invoiceService->retreiveBooking($request->all());
    }

    public function index(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::BillingAndInvoices, PermissionAction::Read);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);

        return $this->invoiceService->retrieveAllBooking($request->user(), $request->all());
    }

    // public function patientInvoiceSummary(Request $request)
    // {
    //     $branch = BranchGuard::resolveBranch($request->branch_uuid);
    //     AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::BillingAndInvoices, PermissionAction::Read);
    //     $request->merge([
    //         'branch_id' => $branch->branch_id,
    //     ]);
    //     Log::info($request->all());
    //     return $this->invoiceService->getPatientSummary($request->user(), $request->all());
    // }
}
