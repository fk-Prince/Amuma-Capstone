<?php

namespace App\Repository;

use App\Models\Bed;
use App\Models\Booking;
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

    public function findAllByConditions(array $conditions)
    {
        return Room::where($conditions)->get();
    }

    public function paginate(string $branch_id, array $payload, int $perPage = 20)
    {
        $query = Room::with([
            'beds.currentAdmission.patient',
            'beds.reservedAdmission.bookings',
        ])->where('branch_id', $branch_id);

        if (!empty($payload['room_type'])) {
            $query->where('room_type', $payload['room_type']);
        }

        if (!empty($payload['search'])) {
            $search = trim($payload['search']);

            $query->where(function ($q) use ($search) {

                $q->where('room_no', 'LIKE', "%{$search}%")

                    ->orWhereHas('beds.currentAdmission.patient', function ($patientQuery) use ($search) {
                        $patientQuery->where(function ($p) use ($search) {
                            $p->where('first_name', 'LIKE', "%{$search}%")
                                ->orWhere('last_name', 'LIKE', "%{$search}%")
                                ->orWhereRaw(
                                    "CONCAT(first_name, ' ', last_name) LIKE ?",
                                    ["%{$search}%"]
                                );
                        });
                    })

                    ->orWhereHas('beds.reservedAdmission.patient', function ($patientQuery) use ($search) {
                        $patientQuery->where(function ($p) use ($search) {
                            $p->where('first_name', 'LIKE', "%{$search}%")
                                ->orWhere('last_name', 'LIKE', "%{$search}%")
                                ->orWhereRaw(
                                    "CONCAT(first_name, ' ', last_name) LIKE ?",
                                    ["%{$search}%"]
                                );
                        });
                    });
            });
        }

        return $query->paginate($perPage);
    }


    public function getRoomStats(int $branchId): array
    {
        $rooms = Room::where('branch_id', $branchId);

        $beds = Bed::whereHas('room', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        });

        $totalRooms = (clone $rooms)->count();

        $occupiedBeds = (clone $beds)
            ->whereHas('currentAdmission')
            ->count();

        $reservedBeds = (clone $beds)
            ->whereHas('reservedAdmission')
            ->count();

        $availableBeds = (clone $beds)
            ->whereDoesntHave('currentAdmission')
            ->whereDoesntHave('reservedAdmission')
            ->where('status', '!=', Bed::STATUS_MAINTENANCE)
            ->count();

        $maintenanceBeds = (clone $beds)
            ->where('status', Bed::STATUS_MAINTENANCE)
            ->count();

        $newThisMonth = (clone $rooms)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            'total_rooms' => [
                'value' => $totalRooms,
                'secondary' => "+{$newThisMonth} new this month",
                'trend' => 'up',
            ],

            'available' => [
                'value' => $availableBeds,
                'secondary' => "{$availableBeds} beds available",
                'trend' => 'up',
            ],

            'occupied' => [
                'value' => $occupiedBeds,
                'secondary' => "{$occupiedBeds} occupied / {$reservedBeds} reserved",
                'trend' => 'up',
            ],

            'maintenance' => [
                'value' => $maintenanceBeds,
                'secondary' => $maintenanceBeds > 0
                    ? 'Requires Attention'
                    : 'No issues detected',
                'trend' => $maintenanceBeds > 0 ? 'warning' : 'success',
            ],
        ];
    }
}
