<?php

namespace App\Repository;

use App\Models\Review;

class ReviewRepository
{
    public function paginate(
        int $perPage,
        string $branch_uuid,
        ?int $rate = null,
        bool $withComments = false
    ) {

        $query = Review::query()
            ->whereHas(
                'branch',
                fn($bq) =>
                $bq->where('uuid', $branch_uuid)
            )
            ->when(
                $rate !== null,
                fn($q) =>
                $q->whereRaw('ROUND(rate) = ?', [$rate])
            )
            ->when(
                $withComments,
                fn($q) =>
                $q->whereNotNull('description')
                    ->where('description', '!=', '')
            );

        $reviews = (clone $query)
            ->with('user')
            ->orderByRaw('ROUND(rate) DESC')
            ->latest()
            ->paginate($perPage);

        $statsQuery = Review::query()
            ->whereHas(
                'branch',
                fn($bq) =>
                $bq->where('uuid', $branch_uuid)
            );

        $averageRating = (clone $statsQuery)->avg('rate');

        $starCounts = (clone $statsQuery)
            ->selectRaw('ROUND(rate) as star, COUNT(*) as total')
            ->groupBy('star')
            ->pluck('total', 'star');

        $ratingBreakdown = collect(range(1, 5))
            ->mapWithKeys(fn($star) => [
                $star => $starCounts[$star] ?? 0
            ]);

        $withCommentsCount = (clone $statsQuery)
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->count();

        return [
            'paginator' => $reviews,
            'average_rating' => round($averageRating, 2),
            'rating_breakdown' => $ratingBreakdown,
            'with_comments_count' => $withCommentsCount,
        ];
    }

    public function create(array $payload)
    {
        return Review::create($payload);
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
