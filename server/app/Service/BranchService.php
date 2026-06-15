<?php

namespace App\Service;

use App\Repository\BranchRepository;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Models\User;

class BranchService
{
    private BranchRepository $branchRepository;

    public function __construct(BranchRepository $branchRepository)
    {
        $this->branchRepository = $branchRepository;
    }

    public function getBranches(array $payload)
    {
        $branch = $this->branchRepository->paginate($payload['per_page']);
        return BranchResource::collection($branch);
    }
}
