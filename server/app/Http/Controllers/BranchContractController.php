<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Requests\BranchContract\StoreBranchContractRequest;
use App\Service\BranchContractService;
use Illuminate\Http\Request;

class BranchContractController extends Controller
{
    private BranchContractService $branchContractService;

    public function __construct(BranchContractService $branchContractService)
    {
        $this->branchContractService = $branchContractService;
    }

    public function store(StoreBranchContractRequest $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        BranchGuard::mergeRequest($request, $branch);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Contracts, PermissionAction::Create);
        return $this->branchContractService->createBranchContract($request->all());
    }

    public function overview(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        BranchGuard::mergeRequest($request, $branch);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Contracts, PermissionAction::Read);
        return $this->branchContractService->overview($request->all());
    }

    public function index(Request $request)
    {
        $type = $request->input('type', 'listing');
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        BranchGuard::mergeRequest($request, $branch);
        if ($type === 'room_contract') {
            return $this->branchContractService->roomContract($request->all());
        }

        if ($type === 'listing') {
            return $this->branchContractService->list($request->all());
        }
    }

    public function update(Request $request, string $id)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        BranchGuard::mergeRequest($request, $branch);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Contracts, PermissionAction::Update);
        return $this->branchContractService->updateBranchContract($request->all(), $id);
    }
}
