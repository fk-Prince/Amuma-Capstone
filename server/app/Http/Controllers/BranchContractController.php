<?php

namespace App\Http\Controllers;

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
        return $this->branchContractService->createBranchContract($request->user(), $request->all());
    }

    public function overview(Request $request)
    {
        return $this->branchContractService->overview($request->user(), $request->all());
    }

    public function index(Request $request)
    {
        return $this->branchContractService->list($request->user(), $request->all());
    }

    public function update(Request $request, string $id)
    {
        return $this->branchContractService->updateBranchContract($request->user(), $request->all(), $id);
    }
}
