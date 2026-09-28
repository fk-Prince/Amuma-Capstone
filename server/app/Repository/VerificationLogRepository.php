<?php

namespace App\Repository;

use App\Models\VerificationLog;

class VerificationLogRepository
{
    public function create(array $payload): VerificationLog
    {
        return VerificationLog::create($payload);
    }

    public function forBranch(int $branchId)
    {
        return $this->query()
            ->whereHas('branchSubscription', fn($query) => $query->where('branch_id', $branchId))
            ->get();
    }

    public function forAgency(int $agencyId)
    {
        return $this->query()
            ->whereHas('branchSubscription.branch', fn($query) => $query->where('agency_id', $agencyId))
            ->get();
    }

    private function query()
    {
        return VerificationLog::query()
            ->with([
                'actor.systemOwner',
                'branchSubscription.branch:branch_id,uuid,name',
            ])
            ->latest('verification_log_id');
    }
}
