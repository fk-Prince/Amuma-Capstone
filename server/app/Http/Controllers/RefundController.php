<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Models\Patient;
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

        $validated = $request->validate([
            'patient_id' => ['required', 'integer'],
            'method' => ['required', 'string', 'max:100'],
            'account_details' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'gt:0'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        return $this->refundService->requestPortalRefund($this->guardedPatient($user, $validated['patient_id']), $validated, $user);
    }

    public function index(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());

        $validated = $request->validate([
            'patient_id' => ['required', 'integer'],
        ]);

        return $this->refundService->requestsForPatient(
            $this->guardedPatient($user, $validated['patient_id'])
        );
    }

    public function issue(Request $request)
    {
        $validated = $request->validate([
            'p_uuid' => ['required', 'string', 'exists:patients,uuid'],
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
            PermissionAction::ApproveWithdrawal
        );

        $patient = Patient::where('uuid', $validated['p_uuid'])->first();

        if (!$patient) {
            throw new Exception('Patient not found.', 404);
        }

        return $this->refundService->withdrawFromDashboard($patient, $validated);
    }

    private function guardedPatient(object $user, mixed $patientId): Patient
    {
        $access = PatientAccess::where('patient_id', $patientId)
            ->where('client_id', $user->client->client_id)
            ->where('have_access', true)
            ->first();

        if (!$access) {
            throw new Exception('You do not have access to this patient.', 403);
        }

        return $access->patient;
    }
}
