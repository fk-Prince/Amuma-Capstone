<?php

namespace App\Repository;

use App\Models\Review;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;

class ReviewRepository
{
    private function cacheKey(string $branchUuid): string
    {
        return "reviews:branch:{$branchUuid}";
    }


    private function loadReviews(string $branchUuid)
    {
        return Cache::rememberForever(
            $this->cacheKey($branchUuid),
            fn() => Review::query()
                ->whereHas('branch', fn($q) => $q->where('uuid', $branchUuid))
                ->with('user')
                ->orderByRaw('ROUND(rate) DESC')
                ->latest()
                ->get()
        );
    }

    public function paginate(int $perPage, string $branch_uuid,  ?int $rate = null,  bool $withComments = false)
    {
        $all = $this->loadReviews($branch_uuid);

        $filtered = $all->filter(function ($review) use ($rate, $withComments) {
            if ($rate !== null && (int) round($review->rate) !== $rate) {
                return false;
            }

            if ($withComments && empty($review->description)) {
                return false;
            }

            return true;
        })->values();

        $page = Paginator::resolveCurrentPage() ?: 1;

        $items = $filtered->slice(($page - 1) * $perPage, $perPage)->values();

        $reviews = new LengthAwarePaginator(
            $items,
            $filtered->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );

        $starCounts = $all
            ->groupBy(fn($review) => (int) round($review->rate))
            ->map->count();

        $ratingBreakdown = collect(range(1, 5))
            ->mapWithKeys(fn($star) => [
                $star => $starCounts[$star] ?? 0
            ]);

        $withCommentsCount = $all
            ->filter(fn($review) => !empty($review->description))
            ->count();

        return [
            'paginator' => $reviews,
            'average_rating' => round($all->avg('rate') ?? 0, 2),
            'rating_breakdown' => $ratingBreakdown,
            'with_comments_count' => $withCommentsCount,
        ];
    }

    public function create(array $payload, ?string $branchUuid = null)
    {
        $review = Review::create($payload);

        if ($branchUuid) {
            $review->load('user');

            $key = $this->cacheKey($branchUuid);
            $cached = Cache::get($key);

            if ($cached !== null) {
                $updated = $cached->push($review)->sort(function ($a, $b) {
                    $rateCompare = round($b->rate) <=> round($a->rate);

                    return $rateCompare !== 0
                        ? $rateCompare
                        : $b->created_at <=> $a->created_at;
                })->values();

                Cache::forever($key, $updated);
            } else {
                $this->loadReviews($branchUuid);
            }
        }

        return $review;
    }

    public function findByUuid(string $uuid)
    {
        return Review::where('uuid', $uuid)->first();
    }

    public function update(string $uuid, array $payload)
    {
        $model = $this->findByUuid($uuid);
        if ($model) {
            $model->update($payload);
        }
        return $model;
    }

    public function delete(string $uuid)
    {
        $model = $this->findByUuid($uuid);
        if ($model) {
            return $model->delete();
        }
        return false;
    }

    public function restore(string $uuid)
    {
        $model = Review::withTrashed()->where('uuid', $uuid)->firstOrFail();
        $model->restore();
        return $model;
    }
}
