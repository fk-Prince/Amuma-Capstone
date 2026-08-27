<?php

namespace App\Service;

use App\Guard\BranchGuard;
use App\Repository\CategoryRepository;
use App\Models\User;
use App\Repository\BranchRepository;

class CategoryService
{

    public function __construct(private CategoryRepository $categoryRepository) {}


    public function listCategory(User $actor, array $payload)
    {
        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        return response()->json([
            'data' => $this->categoryRepository->getCategoriesByBranch($branch->branch_id) ?? []
        ], 200);
    }
}
