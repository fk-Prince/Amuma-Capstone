<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Service\PatientService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PatientController extends Controller
{
    private PatientService $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;
    }

    public function index(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Patients, PermissionAction::Read);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->patientService->retrievePatients($request->all(), $request->user());
    }

    public function show(Request $request, string $uuid)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Patients, PermissionAction::Read);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->patientService->showPatient($uuid);
    }
}
