<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Models\Patient;
use App\Service\DepositService;
use Exception;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    public function __construct(
        private DepositService $depositService
    ) {}

    public function store(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());

        $validated = $request->validate([
            'patient_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'token_id' => ['required', 'string'],
            'authentication_id' => ['required', 'string'],
        ]);

        return $this->depositService->depositFromPortal($user->client, $validated);
    }

    public function issue(Request $request)
    {
        $validated = $request->validate([
            'p_uuid' => ['required', 'string', 'exists:patients,uuid'],
            'branch_uuid' => ['required', 'string', 'exists:branches,uuid'],
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'deposited_by' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $branch = BranchGuard::resolveBranch($validated['branch_uuid']);

        AuthGuard::requireModule(
            $request->user(),
            $branch->branch_id,
            ModuleEnum::BillingAndInvoices,
            PermissionAction::Create
        );

        $patient = Patient::where('uuid', $validated['p_uuid'])->first();

        if (!$patient) {
            throw new Exception('Patient not found.', 404);
        }

        if ($patient->branch_id && (int) $patient->branch_id !== (int) $branch->branch_id) {
            throw new Exception('This patient belongs to another branch.', 403);
        }

        return $this->depositService->depositFromDashboard($patient, $branch->branch_id, $validated);
    }
}
