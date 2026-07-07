<?php

namespace App\Repository;

use App\Models\Notification;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class NotificationRepository
{
    public function paginate(int $perPage = 15, string $user_id, ?string $branch_id = null)
    {
        $query = Notification::latest()
            ->where('to_user_id', $user_id);

        if ($branch_id) {
            $query->where('branch_id', $branch_id);
        }

        return $query->paginate($perPage);
    }

    public function create(array $payload)
    {
        return Notification::create($payload);
    }

    // public function findByUuid(string $uuid)
    // {
    //     return Notification::where('uuid', $uuid)->first();
    // }

    // public function update(string $uuid, array $payload)
    // {
    //     $model = $this->findByUuid($uuid);
    //     if ($model) {
    //         $model->update($payload);
    //     }
    //     return $model;
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
    //     $model = Notification::withTrashed()->where('uuid', $uuid)->firstOrFail();
    //     $model->restore();
    //     return $model;
    // }
}
