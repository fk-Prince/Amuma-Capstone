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

    public function getHighestReviewPaginate(int $perPage)
    {
        return Branch::with([
            'subscriptions.plans',
            'reviews',
            'locations'
        ])
            ->withAvg('reviews', 'rate')
            ->orderByDesc('reviews_avg_rate')
            ->latest()
            ->paginate($perPage);
    }

    // public function paginate(int $perPage)
    // {
    //     return Branch::with([
    //         'subscriptions.plans',
    //         'reviews',
    //         'locations'
    //     ])
    //         ->latest()
    //         ->paginate($perPage);
    // }

    public function getFilterBranches(int $perPage, array $filters)
    {
        return Branch::with([
            'subscriptions.plans',
            'reviews',
            'locations'
        ])
            ->withAvg('reviews', 'rate')

            ->when(!empty($filters['city']), function ($query) use ($filters) {
                $query->whereHas('locations', function ($q) use ($filters) {
                    $q->where('city', 'ILIKE', $filters['city']);
                });
            })

            ->when(!empty($filters['provider_name']), function ($query) use ($filters) {
                $query->where('branch_name', 'ilike', $filters['provider_name']);
            })

            ->when(
                !empty($filters['plan_code']) && $filters['plan_code'] !== 'C',
                function ($query) use ($filters) {
                    $query->whereHas('subscriptions.plans', function ($q) use ($filters) {
                        $q->where('plan_code', $filters['plan_code']);
                    });
                }
            )
            ->orderByDesc('reviews_avg_rate')
            ->paginate($perPage);
    }
}
