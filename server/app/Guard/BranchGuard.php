<?php

namespace App\Guard;

use App\Repository\BranchRepository;
use Exception;

class BranchGuard
{
    public static function resolveBranch(BranchRepository $branchRepository, string $id)
    {
        $branch = $branchRepository->findByField('uuid',   $id);
        if (!$branch) {
            throw new Exception("Branch doesn't exist.", 404);
        }
        return $branch;
    }
}
