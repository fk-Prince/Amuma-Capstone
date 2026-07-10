<?php

namespace App\Repository;

use App\Models\Service;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ServiceRepository
{
    public function create(array $payload)
    {
        return Service::create($payload);
    }

    public function existsInBranch(int $branchId, string $col,  string $value): bool
    {
        return Service::where('branch_id', $branchId)
            ->where($col, $value)
            ->exists();
    }

    public function findByFields(array $conditions)
    {
        return Service::where($conditions)->first();
    }
    public function update() {}
}
