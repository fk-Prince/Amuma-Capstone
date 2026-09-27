<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Requests\CaregiverShift\CaregiverShiftRequest;
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
        AuthGuard::requireModule($request->user(), $branch->branch_id, [ModuleEnum::Admissions, ModuleEnum::Schedules], PermissionAction::Read);
        BranchGuard::mergeRequest($request, $branch);

        return $this->caregiverShifts->list($request->all());
    }

    public function board(Request $request)
    {
        $request->validate([
            'branch_uuid' => ['required', 'uuid'],
            'search' => ['nullable', 'string', 'max:100'],
            'assigned_only' => ['nullable', 'boolean'],
        ]);
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Schedules, PermissionAction::Read);
        BranchGuard::mergeRequest($request, $branch);

        return $this->caregiverShifts->board($request->all(), AuthGuard::requireUser($request->user()));
    }

    public function store(CaregiverShiftRequest $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, [ModuleEnum::Admissions, ModuleEnum::Schedules], PermissionAction::Update);
        BranchGuard::mergeRequest($request, $branch);
        return $this->caregiverShifts->assign($request->all());
    }

    public function update(CaregiverShiftRequest $request, string $id)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, [ModuleEnum::Admissions, ModuleEnum::Schedules], PermissionAction::Update);
        BranchGuard::mergeRequest($request, $branch);
        $request->merge(['id' => $id]);
        return $this->caregiverShifts->update($request->all());
    }
}
