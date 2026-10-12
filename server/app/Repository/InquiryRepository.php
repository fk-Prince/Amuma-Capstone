<?php

namespace App\Repository;

use App\Models\Inquiry;

class InquiryRepository
{
    public function create(array $payload)
    {
        return Inquiry::create($payload);
    }

    public function paginateForBranch(int $branchId, array $filters, int $perPage)
    {
        return Inquiry::query()
            ->select([
                'inquiry_id',
                'uuid',
                'first_name',
                'last_name',
                'email',
                'phone_number',
                'city',
                'message',
                'status',
                'reply',
                'replied_at',
                'created_at',
            ])
            ->where('branch_id', $branchId)
            ->when($filters['status'] ?? null, fn($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $term = '%' . trim($search) . '%';

                $query->where(function ($inner) use ($term) {
                    $inner->where('first_name', 'ilike', $term)
                        ->orWhere('last_name', 'ilike', $term)
                        ->orWhere('email', 'ilike', $term)
                        ->orWhere('phone_number', 'ilike', $term)
                        ->orWhere('city', 'ilike', $term);
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function statusCounts(int $branchId)
    {
        return Inquiry::query()
            ->where('branch_id', $branchId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
    }

    public function findForBranch(string $uuid, int $branchId)
    {
        return Inquiry::query()
            ->where('uuid', $uuid)
            ->where('branch_id', $branchId)
            ->first();
    }

    public function update(Inquiry $inquiry, array $payload)
    {
        $inquiry->update($payload);

        return $inquiry;
    }
}
