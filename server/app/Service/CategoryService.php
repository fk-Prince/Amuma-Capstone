<?php

namespace App\Service;

use App\Guard\BranchGuard;
use App\Repository\CategoryRepository;
use App\Http\Resources\CategoryResource;
use App\Models\User;
use App\Repository\BranchRepository;
use Exception;

class CategoryService
{
    private CategoryRepository $categoryRepository;
    private BranchRepository $branchRepository;

    public function __construct(CategoryRepository $categoryRepository, BranchRepository $branchRepository)
    {
        $this->categoryRepository = $categoryRepository;
        $this->branchRepository = $branchRepository;
    }


    public function listCategory(User $actor, array $payload)
    {
        $branch = BranchGuard::resolveBranch($this->branchRepository, $payload['branch_uuid']);
        return response()->json([
            'data' => $this->categoryRepository->getCategoriesByBranch($branch->branch_id) ?? []
        ], 200);
    }
}
