<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Service\VitalService;
use Illuminate\Http\Request;

class VitalController extends Controller
{
    public function __construct(
        private VitalService $vitalService
    ) {}

    public function index(Request $request)
    {
        $this->requireMedications($request, PermissionAction::Read, [ModuleEnum::Medications, ModuleEnum::Patients]);
        return $this->vitalService->listVitals($request->all());
    }

    public function store(Request $request)
    {
        $this->requireMedications($request, PermissionAction::Create);
        return $this->vitalService->createVital($request->user(), $request->all());
    }

    public function update(Request $request, string $id)
    {
        $this->requireMedications($request, PermissionAction::Update);
        return $this->vitalService->updateVital($request->user(), $request->all(), $id);
    }

    private function requireMedications(Request $request, PermissionAction $action, ModuleEnum|array $module = ModuleEnum::Medications): void
    {
        $branch = BranchGuard::resolveBranch((string) $request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, $module, $action);
        BranchGuard::mergeRequest($request, $branch);
    }
}
