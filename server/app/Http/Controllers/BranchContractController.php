<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Requests\BranchContract\StoreBranchContractRequest;
use App\Service\BranchContractService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Contracts,  PermissionAction::Create);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->branchContractService->createBranchContract($request->user(), $request->all());
    }

    public function overview(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Contracts,  PermissionAction::Read);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->branchContractService->overview($request->user(), $request->all());
    }

    public function index(Request $request)
    {
        $type = $request->input('type', 'listing');

        if ($type === 'room_contract') {
            $branch = BranchGuard::resolveBranch($request->branch_uuid);
            $request->merge([
                'branch_id' => $branch->branch_id,
            ]);
            AuthGuard::requireModule($request->user(),  $branch->branch_id, ModuleEnum::Contracts, PermissionAction::Update); // used also in Contracts,admission
            return $this->branchContractService->roomContract(
                $request->user(),
                $request->all()
            );
        } elseif ($type === 'listing') {
            $branch = BranchGuard::resolveBranch($request->branch_uuid);
            $request->merge([
                'branch_id' => $branch->branch_id,
            ]);
            AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Contracts,  PermissionAction::Read);
            return $this->branchContractService->list(
                $request->user(),
                $request->all()
            );
        }
    }

    public function update(Request $request, string $id)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(),  $branch->branch_id, ModuleEnum::Contracts, PermissionAction::Update);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->branchContractService->updateBranchContract($request->user(), $request->all(), $id);
    }
}
