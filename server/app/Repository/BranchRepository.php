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

    public function getUserBrancheses()
    {
        return Branch::all();
    }

    public function getUserBranchesesByField(string $column, string $value)
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
            'location'
        ])
            ->withAvg('reviews', 'rate')
            ->orderByDesc('reviews_avg_rate')
            ->latest()
            ->paginate($perPage);
    }

    public function getUserBranches(array $branchIds)
    {
        return  Branch::with(['location', 'subscriptions.plans', 'agencies.locations'])
            ->whereIn('branch_id', $branchIds)
            ->get()
            ->keyBy('branch_id');
    }

    public function getFilterBranches(int $perPage, array $filters)
    {
        return Branch::with([
            'subscriptions.plans',
            'reviews',
            'location'
        ])
            ->withAvg('reviews', 'rate')

            ->when(!empty($filters['city']), function ($query) use ($filters) {
                $query->whereHas('location', function ($q) use ($filters) {
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

    public function getBranch(string $uuid)
    {
        return Branch::with([
            'subscriptions.plans',
            'location',
            'agencies',
            // 'services' => function ($query) {
            //     $query->where('is_available', true)
            //         ->with('categories');
            // },
        ])
            ->withAvg('reviews', 'rate')
            ->withCount('reviews')
            ->where('uuid', $uuid)
            ->first();
    }
}
