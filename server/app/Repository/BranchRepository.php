<?php

namespace App\Repository;

use App\Models\Branch;
use App\Models\BranchImage;

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

    // public function getUserBranchesesByField(string $column, string $value)
    // {
    //     return Branch::all();
    // }


    public function findByField(string $column, string $value)
    {
        return Branch::where($column, $value)->first();
    }

    public function getHighestReviewPaginate(int $perPage)
    {
        return Branch::with([
            'subscriptions.plans',
            'reviews',
            'location',
            'contracts',
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

    public function getFilterBranches(int $perPage, array $filters, string $sort = 'recommended',   ?float $lat = null,  ?float $long = null)
    {
        $query = Branch::with([
            'subscriptions.plans',
            'reviews',
            'location',
            'contracts',
        ])
            ->withAvg('reviews', 'rate')
            ->withCount('reviews')
            ->withCount('bookings')

            ->when(!empty($filters['city']), function ($query) use ($filters) {
                $query->whereHas('location', function ($q) use ($filters) {
                    $q->where('city', 'ILIKE', '%' . $filters['city'] . '%');
                });
            })
            ->when(!empty($filters['provider_name']), function ($query) use ($filters) {
                $query->where('name', 'ILIKE', '%' . $filters['provider_name'] . '%');
            })

            ->when(
                !empty($filters['plan_code']) && $filters['plan_code'] !== 'C',
                function ($query) use ($filters) {
                    $query->whereHas('subscriptions.plans', function ($q) use ($filters) {
                        $q->where('plan_code', $filters['plan_code']);
                    });
                }
            );

        if ($sort === 'nearest' && $lat !== null && $long !== null) {
            $distanceExpr = 'CASE
                WHEN locations.latitude IS NULL OR locations.longitude IS NULL THEN NULL
                ELSE (6371 * acos(LEAST(1, GREATEST(-1,
                    cos(radians(?)) * cos(radians(locations.latitude)) * cos(radians(locations.longitude) - radians(?))
                    + sin(radians(?)) * sin(radians(locations.latitude))
                ))))
            END';

            $query
                ->leftJoin('locations', 'locations.location_id', '=', 'branches.location_id')
                ->selectRaw("{$distanceExpr} as distance_km", [$lat, $long, $lat])
                ->orderByRaw(
                    "({$distanceExpr}) IS NULL, ({$distanceExpr}) ASC",
                    [$lat, $long, $lat, $lat, $long, $lat]
                );
        } elseif ($sort === 'highest_rated') {
            $query->orderByDesc('reviews_avg_rate');
        } elseif ($sort === 'most_popular') {
            $query->orderByDesc('bookings_count');
        } else {
            $query->orderByDesc('reviews_avg_rate')->orderByDesc('reviews_count');
        }

        return $query->paginate($perPage);
    }

    public function getBranch(string $uuid)
    {
        return Branch::with([
            'subscriptions.plans',
            'location',
            'contracts',
            'agencies',
            'services',
            'bookings',
            'rooms',
            'images' => function ($query) {
                $query->whereIn('type', [
                    BranchImage::IMAGE_BRANCH,
                    BranchImage::IMAGE_COMMON_ROOM,
                    BranchImage::IMAGE_VIP_ROOM,
                ]);
            },
        ])
            ->withAvg('reviews', 'rate')
            ->withCount('reviews')
            ->where('uuid', $uuid)
            ->first();
    }
}
