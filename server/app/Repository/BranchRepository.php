<?php

namespace App\Repository;

use App\Models\Branch;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BranchRepository
{

    public function create(array $payload)
    {
        return Branch::create($payload);
    }

    public function getBranches()
    {
        return Branch::all();
    }

    public function findByField(string $column, string $value)
    {
        return Branch::where($column, $value)->first();
    }

    public function paginate(int $perPage)
    {
        return Branch::with([
            'subscriptions.plans',
            'reviews',
            'locations'
        ])
            ->latest()
            ->paginate($perPage);
    }
}
