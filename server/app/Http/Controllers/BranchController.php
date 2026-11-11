<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Requests\Branch\BranchRequest;
use App\Service\BranchService;
use App\Service\BranchSetupService;
use App\Service\BranchTermsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class BranchController extends Controller
{

    public function __construct(
        private BranchService $branchService,
        private BranchSetupService $branchSetupService,
        private BranchTermsService $branchTermsService,
    ) {}

    public function setup(Request $request, string $uuid)
    {
        $branch = BranchGuard::resolveBranch($uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::cases(), PermissionAction::Read);

        return response()->json(['data' => $this->branchSetupService->status($branch)]);
    }

    public function setupVisit(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'step' => ['required', 'in:' . implode(',', BranchSetupService::VISITS)],
        ]);

        $branch = BranchGuard::resolveBranch($uuid);
        AuthGuard::requireModule(
            $request->user(),
            $branch->branch_id,
            $validated['step'] === 'subscription' ? ModuleEnum::ManageSubscription : ModuleEnum::BranchSettings,
            PermissionAction::Read
        );

        return response()->json(['data' => $this->branchSetupService->markVisit($branch, $validated['step'])]);
    }


    public function fetchBranch(string $uuid)
    {
        return $this->branchService->getBranch($uuid);
    }

    public function terms(string $uuid)
    {
        return response()->json(['data' => $this->branchTermsService->forBranch($uuid)]);
    }

    public function healthProviders(string $uuid)
    {
        return $this->branchService->getHealthProviders($uuid);
    }

    public function retrieveFeaturedBranch(Request $request)
    {
        return $this->branchService->getFeaturedBranches($request->all());
    }

    public function retrieveFilteredBranch(Request $request)
    {
        return $this->branchService->getBranchesByFilter(
            array_merge($request->all(), ['ip' => $request->ip()])
        );
    }

    public function validate(BranchRequest $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Validation passed',
            'data' => $request->validated(),
        ]);
    }

    public function update(BranchRequest $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid, active: true);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::BranchSettings, PermissionAction::Update);
        BranchGuard::mergeRequest($request, $branch);
        $request->merge(['branch' => $branch]);
        return $this->branchService->updateBranch($request->all());
    }

    public function dashboard(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        BranchGuard::mergeRequest($request, $branch);
        $payload = $request->all();
        $payload['viewer_user_id'] = $request->user()?->user_id;
        return $this->branchService->dashboard($payload);
    }
}