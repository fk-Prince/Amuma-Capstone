<?php

namespace App\Repository;

use App\Models\Booking;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BookingRepository
{
    public function paginate(string $branchId, array $payload)
    {
        return Booking::with('user')
            ->where('branch_id', $branchId)
            ->when(
                isset($payload['category']) && $payload['category'] !== 'all',
                function ($query) use ($payload) {
                    $query->where('category', $payload['category']);
                }
            )
            ->when(
                !empty($payload['status']),
                function ($query) use ($payload) {
                    $query->where('status', $payload['status']);
                }
            )
            ->when(
                !empty($payload['search']),
                function ($query) use ($payload) {
                    $search = $payload['search'];

                    $query->where(function ($q) use ($search) {
                        $q->where('reference_id', 'ilike', "%{$search}%")
                            ->orWhereRaw(
                                "LOWER(booking_data->'patient'->>'first_name') LIKE ?",
                                ['%' . strtolower($search) . '%']
                            )
                            ->orWhereRaw(
                                "LOWER(booking_data->'patient'->>'middle_name') LIKE ?",
                                ['%' . strtolower($search) . '%']
                            )
                            ->orWhereRaw(
                                "LOWER(booking_data->'patient'->>'last_name') LIKE ?",
                                ['%' . strtolower($search) . '%']
                            )
                            ->orWhereRaw(
                                "LOWER(CONCAT(booking_data->'patient'->>'first_name', ' ', booking_data->'patient'->>'last_name')) LIKE ?",
                                ['%' . strtolower($search) . '%']
                            );
                    });
                }
            )
            ->orderByDesc('created_at')
            ->paginate($payload['per_page'] ?? 10);
    }

    public function create(array $payload)
    {
        return Booking::create($payload);
    }

    public function findByField(array $conditions)
    {
        return Booking::where($conditions)->first();
    }
}
