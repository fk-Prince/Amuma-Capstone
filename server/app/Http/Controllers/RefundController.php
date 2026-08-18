<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Service\InvoiceService;
use App\Service\RefundService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RefundController extends Controller
{
    // private RefundService $refundService;
    // private InvoiceService $invoiceService;

    // public function __construct(RefundService $refundService)
    // {
    //     $this->refundService = $refundService;
    // }


    // public function store(Request $request)
    // {
    //     $branch = BranchGuard::resolveBranch($request->branch_uuid);
    //     AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::BillingAndInvoices, PermissionAction::Read);
    //     $request->merge([
    //         'branch_id' => $branch->branch_id,
    //     ]);
    //     return $this->refundService->completeRefund($request->all());
    // }
}
