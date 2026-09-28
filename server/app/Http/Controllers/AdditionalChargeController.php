<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Requests\AdditionalCharge\AdditionalChargeRequest;
use App\Service\AdditionalChargeService;
use Illuminate\Http\Request;

class AdditionalChargeController extends Controller
{
    public function __construct(private AdditionalChargeService $additionalCharges) {}

    public function index(Request $request)
    {
        $request->validate([
            'branch_uuid' => ['required', 'uuid'],
            'patient_uuid' => ['required', 'uuid'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Admissions, PermissionAction::Read);
        BranchGuard::mergeRequest($request, $branch);

        return $this->additionalCharges->list($request->all());
    }

    public function store(AdditionalChargeRequest $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Admissions, PermissionAction::Update);
        BranchGuard::mergeRequest($request, $branch);

        return $this->additionalCharges->store($request->all());
    }
}
