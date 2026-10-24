<?php

namespace App\Guard;

use App\Models\EmployeeBranch;
use App\Repository\BranchRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchGuard
{
    protected static ?BranchRepository $branchRepository = null;

    public function __construct(BranchRepository $branchRepository)
    {
        self::$branchRepository = $branchRepository;
    }

    public static function resolveBranch(string $id, bool $facility = false, bool $homecare = false, bool $active = false)
    {
        if (!self::$branchRepository) {
            self::$branchRepository = app(BranchRepository::class);
        }

        $branch = self::$branchRepository->findByField('uuid', $id);

        if (!$branch) {
            throw new Exception("Branch doesn't exist.", 404);
        }

        $missing = match (true) {
            $facility && !$branch->hasFacilitySubscription() => __('No active facility subscription.'),
            $homecare && !$branch->hasHomecareSubscription() => __('No active homecare subscription.'),
            $active && !$branch->hasActiveSubscription() => __('No active subscription.'),
            default => null,
        };

        if ($missing) {
            AuthGuard::requireOpenSubscription($branch->branch_id);

            throw new Exception($missing, 403);
        }

        $employee = Auth::user()?->employee;

        if ($employee) {
            $employeeBranch = $employee->employeeBranch()
                ->where('branch_id', $branch->branch_id)
                ->first();

            if ($employeeBranch && $employeeBranch->status === EmployeeBranch::STATUS_INACTIVE) {
                throw new Exception(
                    __('Your account is inactive in this branch. Please contact your branch manager or agency owner to have it reactivated.'),
                    403
                );
            }
        }

        return $branch;
    }

    public static function mergeRequest(Request $request, mixed $branch): void
    {
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
    }
}
