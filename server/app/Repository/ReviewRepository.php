<?php

namespace App\Repository;

use App\Models\Review;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReviewRepository
{
    public function paginate(int $perPage = 15, ?string $companyId = null)
    {
        $query = Review::latest();

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->paginate($perPage);
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