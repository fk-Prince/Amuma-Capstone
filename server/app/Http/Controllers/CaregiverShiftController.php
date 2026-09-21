<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Requests\CaregiverShiftRequest;
use App\Service\CaregiverShiftService;
use Illuminate\Http\Request;

class CaregiverShiftController extends Controller
{
    public function __construct(private CaregiverShiftService $caregiverShifts) {}

    public function index(Request $request)
    {
        $request->validate([
            'branch_uuid' => ['required', 'uuid'],
            'admission_id' => ['required', 'integer'],
        ]);

        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Admissions, PermissionAction::Read);
        BranchGuard::mergeRequest($request, $branch);

        return $this->caregiverShifts->list($request->all());
    }

    public function store(CaregiverShiftRequest $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Admissions, PermissionAction::Update);
        BranchGuard::mergeRequest($request, $branch);

        return $this->caregiverShifts->assign($request->all());
    }

    public function update(CaregiverShiftRequest $request, string $id)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Admissions, PermissionAction::Update);
        BranchGuard::mergeRequest($request, $branch);
        $request->merge(['id' => $id]);

        return $this->caregiverShifts->update($request->all());
    }
}
