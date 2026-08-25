<?php

namespace App\Http\Controllers;

use App\Guard\AuthGuard;
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
            throw new Exception('Only family/client accounts can claim a refund.', 403);
        }

        $validated = $request->validate([
            'patient_id' => ['required', 'integer'],
            'method' => ['required', 'string', 'max:100'],
            'account_details' => ['required', 'string', 'max:255'],
        ]);

        $access = PatientAccess::where('patient_id', $validated['patient_id'])
            ->where('client_id', $user->client->client_id)
            ->where('have_access', true)
            ->first();

        if (!$access) {
            throw new Exception('You do not have access to this patient.', 403);
        }

        return $this->refundService->claimPortalRefund($access->patient, $validated);
    }
}
