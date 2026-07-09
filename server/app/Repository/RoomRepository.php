<?php

namespace App\Repository;

use App\Models\Room;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoomRepository
{

    public function create(array $payload)
    {
        return Room::create($payload);
    }
    public function findByField(array $conditions)
    {
        return Room::where($conditions)->first();
    }

    public function paginate(int $perPage, string $branch_id)
    {
        return Room::with('beds')
            ->where('branch_id', $branch_id)
            ->paginate($perPage);
    }
}
