<?php

namespace App\Repository;

use App\Models\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ScheduleRepository
{
    public function paginate(int $perPage = 15, ?string $companyId = null)
    {
        $query = Schedule::latest();

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->paginate($perPage);
    }

    public function create(array $payload)
    {
        return Schedule::create($payload);
    }

    public function findByUuid(string $uuid)
    {
        return Schedule::where('uuid', $uuid)->first();
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
        $model = Schedule::withTrashed()->where('uuid', $uuid)->firstOrFail();
        $model->restore();
        return $model;
    }
}