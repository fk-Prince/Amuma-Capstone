<?php

namespace App\Http\Controllers;

use App\Http\Requests\BranchRequest;
use App\Service\BranchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class BranchController extends Controller
{
    private BranchService $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    public function show(string $uuid)
    {
        return $this->branchService->getBranch($uuid);
    }

    public function retrieveFeaturedBranch(Request $request)
    {
        return $this->branchService->getFeaturedBranches($request->all());
    }

    public function retrieveFilteredBranch(Request $request)
    {
        return $this->branchService->getBranchesByFilter($request->all());
    }

    public function validate(BranchRequest $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Validation passed',
            'data' => $request->validated(),
        ]);
    }

    public function update(BranchRequest $request, string $uuid)
    {
        return $this->branchService->updateBranch($request->all(), $request->user(), $uuid);
    }
}
