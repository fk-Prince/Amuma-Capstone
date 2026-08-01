<?php

namespace App\Repository;

use App\Models\Booking;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\Service;
use Carbon\Carbon;

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
                !empty($payload['date_from']),
                function ($query) use ($payload) {
                    $query->where('created_at', '>=', $payload['date_from'] . ' 00:00:00');
                }
            )
            ->when(
                !empty($payload['date_to']),
                function ($query) use ($payload) {
                    $query->where('created_at', '<=', $payload['date_to'] . ' 23:59:59');
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

    public function findBookings(array $conditions)
    {
        return Booking::where($conditions)->get();
    }

    public function overview(string $branchId)
    {
        $now = Carbon::now();

        return [
            'bookings' => [
                'pending_confirmation' => Booking::where('branch_id', $branchId)
                    ->where('status', Booking::STATUS_PENDING)
                    ->count(),

                'approved' => Booking::where('branch_id', $branchId)
                    ->where('status', Booking::STATUS_APPROVED)
                    ->count(),

                'awaiting' => Booking::where('branch_id', $branchId)
                    ->where('status', Booking::STATUS_AWAITING)
                    ->count(),

                'completed' => Booking::where('branch_id', $branchId)
                    ->where('status', Booking::STATUS_COMPLETED)
                    ->count(),

                'rejected' => Booking::where('branch_id', $branchId)
                    ->where('status', Booking::STATUS_REJECTED)
                    ->count(),

                'expired' => Booking::where('branch_id', $branchId)
                    ->where('status', Booking::STATUS_EXPIRED)
                    ->count(),

                'expiring_soon' => Booking::where('branch_id', $branchId)
                    ->whereNotNull('valid_until')
                    ->whereBetween('valid_until', [
                        $now,
                        $now->copy()->addHours(24)
                    ])
                    ->count(),

                'today' => Booking::where('branch_id', $branchId)
                    ->whereDate('created_at', today())
                    ->count(),

                'recent' => Booking::where('branch_id', $branchId)
                    ->latest()
                    ->limit(5)
                    ->get([
                        'booking_id',
                        'reference_id',
                        'category',
                        'status',
                        'created_at'
                    ]),
            ],


            'schedule' => [
                'starting_soon' => Schedule::whereHas('patient', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                    ->whereBetween('scheduled_at', [
                        $now,
                        $now->copy()->addHours(2)
                    ])
                    ->count(),

                'today' => Schedule::whereHas('patient', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                    ->whereDate('scheduled_at', today())
                    ->count(),

                'completed_today' => Schedule::whereHas('patient', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                    ->where('status', 'completed')
                    ->whereDate('scheduled_at', today())
                    ->count(),

                'pending' => Schedule::whereHas('patient', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                    ->where('status', 'pending')
                    ->count(),
            ],
            'patients' => [
                'total' => Patient::where('branch_id', $branchId)
                    ->count(),

                'new_today' => Patient::where('branch_id', $branchId)
                    ->whereDate('created_at', today())
                    ->count(),
            ],
        ];
    }
}
