<?php

namespace App\Repository;

use App\Models\Bed;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BedRepository
{
    public function paginate(int $perPage = 15, ?string $companyId = null)
    {
        $query = Bed::latest();

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->paginate($perPage);
    }

    public function create(array $payload)
    {
        return Bed::create($payload);
    }

    public function findByField(array $conditions)
    {
        return Bed::where($conditions)->first();
    }

    // public function findByUuid(string $uuid)
    // {
    //     return Bed::where('uuid', $uuid)->first();
    // }


    // public function delete(string $uuid)
    // {
    //     $model = $this->findByUuid($uuid);
    //     if ($model) {
    //         return $model->delete();
    //     }
    //     return false;
    // }

    // public function restore(string $uuid)
    // {
    //     $model = Bed::withTrashed()->where('uuid', $uuid)->firstOrFail();
    //     $model->restore();
    //     return $model;
    // }
}
