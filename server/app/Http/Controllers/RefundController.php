<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Models\Invoice;
use App\Models\PatientAccess;
use App\Service\RefundService;
use Exception;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function __construct(
        private RefundService $refundService
    ) {}

    public function store(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());

        if (!$user->client) {
            throw new Exception('Only family/client accounts can request a refund.', 403);
        }

        $validated = $request->validate([
            'patient_id' => ['required', 'integer'],
            'method' => ['required', 'string', 'max:100'],
            'account_details' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $access = PatientAccess::where('patient_id', $validated['patient_id'])
            ->where('client_id', $user->client->client_id)
            ->where('have_access', true)
            ->first();

        if (!$access) {
            throw new Exception('You do not have access to this patient.', 403);
        }

        return $this->refundService->requestPortalRefund($access->patient, $validated, $user);
    }

    public function issue(Request $request)
    {
        $validated = $request->validate([
            'invoice_uuid' => ['required', 'string', 'exists:invoices,uuid'],
            'branch_uuid' => ['required', 'string', 'exists:branches,uuid'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'method' => ['nullable', 'string', 'max:100'],
            'account_details' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $branch = BranchGuard::resolveBranch($validated['branch_uuid']);

        AuthGuard::requireModule(
            $request->user(),
            $branch->branch_id,
            ModuleEnum::BillingAndInvoices,
            PermissionAction::Update
        );

        $invoice = Invoice::where('uuid', $validated['invoice_uuid'])
            ->where('branch_id', $branch->branch_id)
            ->first();

        if (!$invoice) {
            throw new Exception('Invoice not found for this branch.', 404);
        }

        return $this->refundService->createRefundFromDashboard($invoice, $validated);
    }
}
