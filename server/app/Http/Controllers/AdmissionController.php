<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Service\AdmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdmissionController extends Controller
{
    private AdmissionService $admissionService;

    public function __construct(AdmissionService $admissionService)
    {
        $this->admissionService = $admissionService;
    }

    public function store(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Admissions, PermissionAction::Create);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->admissionService->storeAdmission($request->all());
    }
}
