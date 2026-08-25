<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Requests\AgencyRequest;
use App\Service\AgencyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AgencyController extends Controller
{
    private AgencyService $agencyService;

    public function __construct(AgencyService $agencyService)
    {
        $this->agencyService = $agencyService;
    }

    public function index(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid, true);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::ManageBranches, PermissionAction::Read);
        BranchGuard::mergeRequest($request, $branch);
        return $this->agencyService->listAgency($request->all());
    }

    public function update(Request $request, string $uuid)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid, true);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::ManageBranches, PermissionAction::Update);
        BranchGuard::mergeRequest($request, $branch);
        $request->merge(['agency_id' => $branch->agency_id]);
        return $this->agencyService->update($request->all());
    }
}
