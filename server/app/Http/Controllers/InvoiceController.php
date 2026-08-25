<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Service\InvoiceService;
use Exception;
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
        BranchGuard::mergeRequest($request, $branch);
        return $this->invoiceService->overview($request->all());
    }

    public function store(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::BillingAndInvoices, PermissionAction::Create);
        BranchGuard::mergeRequest($request, $branch);
        return $this->invoiceService->storeBooking($request->all());
    }

    public function show(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::BillingAndInvoices, PermissionAction::Read);
        BranchGuard::mergeRequest($request, $branch);
        return $this->invoiceService->retreiveBooking($request->all());
    }

    public function index(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::BillingAndInvoices, PermissionAction::Read);
        BranchGuard::mergeRequest($request, $branch);
        return $this->invoiceService->retrieveAllBooking($request->all());
    }

    public function action(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        BranchGuard::mergeRequest($request, $branch);
        if ($request->type === 'refund') {
            return $this->invoiceService->completeRefund($request->all());
        }
    }
}
