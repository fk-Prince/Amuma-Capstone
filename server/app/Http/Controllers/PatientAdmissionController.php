<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Requests\AdmissionRequest;
use App\Service\PatientAdmissionService;
use Illuminate\Http\Request;

class PatientAdmissionController extends Controller
{
    public function __construct(private PatientAdmissionService $patientAdmissionService) {}

    public function admissionAction(AdmissionRequest $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Patients, PermissionAction::Read);
        BranchGuard::mergeRequest($request, $branch);
        return $this->patientAdmissionService->action($request->all());
    }

    public function store(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Admissions, PermissionAction::Create);
        BranchGuard::mergeRequest($request, $branch);
        return $this->patientAdmissionService->storeAdmission($request->user(), $request->all());
    }

    public function action(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Admissions, PermissionAction::Create);
        BranchGuard::mergeRequest($request, $branch);
        $request->merge(['user' => $request->user()]);
        return $this->patientAdmissionService->action($request->all());
    }

    public function show(Request $request, string $id)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        BranchGuard::mergeRequest($request, $branch);
        return $this->patientAdmissionService->show($request->all());
    }

    public function index(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        BranchGuard::mergeRequest($request, $branch);
        return $this->patientAdmissionService->list($request->all());
    }
}
