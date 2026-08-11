<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Service\ScheduleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ScheduleController extends Controller
{
    private ScheduleService $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    public function store(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Schedules, PermissionAction::Read);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->scheduleService->createSchedule($request->user(), $request->all());
    }

    public function index(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Schedules, PermissionAction::Read);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->scheduleService->retrieveSchedule($request->user(), $request->all());
    }

    public function action(Request $request)
    {
        if ($request->type === 'assign') { // USED
            $branch = BranchGuard::resolveBranch($request->branch_uuid);
            // AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Admissions, PermissionAction::Create);
            $request->merge([
                'branch_id' => $branch->branch_id,
            ]);
            return $this->scheduleService->assignEmployee($request->user(), $request->all());
        } else  if ($request->type === 'available_employee') {
            // NOT USED
            $branch = BranchGuard::resolveBranch($request->branch_uuid);
            // AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Admissions, PermissionAction::Create);
            $request->merge([
                'branch_id' => $branch->branch_id,
            ]);
            return $this->scheduleService->availableEmployee($request->all());
        } else if ($request->type === 'overview') {
            $branch = BranchGuard::resolveBranch($request->branch_uuid);
            $request->merge([
                'branch_id' => $branch->branch_id,
            ]);
            return $this->scheduleService->overview($request->all());
        }
    }

    public function update(Request $request, string $id)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Schedules, PermissionAction::Update);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->scheduleService->checkConflictSchedule($request->user(), $request->all());
    }
}
