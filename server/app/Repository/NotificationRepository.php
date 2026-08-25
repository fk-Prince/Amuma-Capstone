<?php

namespace App\Repository;

use App\Models\Notification;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class NotificationRepository
{
    public function paginate(
        int $perPage,
        string $user_id,
        ?string $branch_id = null,
        bool $unreadOnly = false
    ) {
        $query = Notification::latest()
            ->with('branch:branch_id,uuid,name')
            ->where('to_user_id', $user_id);

        if ($branch_id) {
            $query->where('branch_id', $branch_id);
        }

        if ($unreadOnly) {
            $query->where('has_read', false);
        }

        return $query->paginate($perPage);
    }

    public function unreadCount(string $user_id, ?string $branch_id = null): int
    {
        return Notification::where('to_user_id', $user_id)
            ->where('has_read', false)
            ->when($branch_id, fn($query) => $query->where('branch_id', $branch_id))
            ->count();
    }

    /**
     * Marks one notification read, or every unread one when no id is given.
     * Always scoped to the recipient so a stray id cannot touch someone else's.
     */
    public function markRead(string $user_id, ?int $notificationId = null): int
    {
        return Notification::where('to_user_id', $user_id)
            ->where('has_read', false)
            ->when(
                $notificationId,
                fn($query) => $query->where('notification_id', $notificationId)
            )
            ->update(['has_read' => true]);
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
