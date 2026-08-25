<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Service\BranchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BranchSettingController extends Controller
{
    private BranchService $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    public function store(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::BranchSettings, PermissionAction::Create);
        BranchGuard::mergeRequest($request, $branch);
        return $this->branchService->action($request->all());
    }

    public function index(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::BranchSettings, PermissionAction::Read);
        BranchGuard::mergeRequest($request, $branch);
        return $this->branchService->retrieveAction($request->all());
    }

    public function update(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::BranchSettings, PermissionAction::Update);
        BranchGuard::mergeRequest($request, $branch);
        $request->merge(['branch' => $branch]);

        return $this->branchService->updateSettings($request->all());
    }
}
