<?php

namespace App\Service;

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

        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

        if (!$branch) {
            throw new Exception(__('Branch does not exist'), 404);
        }

        return response()->json([
            'data' => $this->categoryRepository->getCategories() ?? []
        ], 200);
    }
}
