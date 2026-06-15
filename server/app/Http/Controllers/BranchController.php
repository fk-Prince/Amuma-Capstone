<?php

namespace App\Http\Controllers;

use App\Service\BranchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BranchController extends Controller
{
    private BranchService $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    public function index(Request $request)
    {
        return $this->branchService->getBranches($request->all());
    }

    public function retrieveBranches(Request $request)
    {
        return;
    }
}
