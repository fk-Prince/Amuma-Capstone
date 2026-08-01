<?php

namespace App\Guard;

use App\Repository\BranchRepository;
use Exception;

class BranchGuard
{
    protected static ?BranchRepository $branchRepository = null;

    public function __construct(BranchRepository $branchRepository)
    {
        self::$branchRepository = $branchRepository;
    }

    public static function resolveBranch(string $id, bool $facility = false)
    {
        if (!self::$branchRepository) {
            self::$branchRepository = app(BranchRepository::class);
        }

        $branch = self::$branchRepository->findByField('uuid', $id);

        if (!$branch) {
            throw new Exception("Branch doesn't exist.", 404);
        }

        if ($facility && !$branch->hasFacilitySubscription()) {
            throw new Exception(__('No active facility subscription.'), 403);
        }

        return $branch;
    }
}
