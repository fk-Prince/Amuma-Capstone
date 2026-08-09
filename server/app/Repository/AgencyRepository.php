<?php

namespace App\Repository;

use App\Models\Agency;
use App\Models\Branch;
use App\Models\Subscription;

class AgencyRepository
{

    public function createAgency(array $payload)
    {
        return Agency::create($payload);
    }


    public function findAgencyByField(string $column, string $value)
    {
        return Agency::where($column, $value)->first();
    }

    public function stats(string $agencyId)
    {
        $totalBranches = Branch::query()
            ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId))
            ->count();

        $activeBranches = Branch::query()
            ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId))
            ->where('status', 'active')
            ->count();

        $newThisMonth = Branch::query()
            ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $expiringSoon = Subscription::query()
            ->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays(14)])
            ->when($agencyId, function ($q) use ($agencyId) {
                $q->whereHas('branch', fn($b) => $b->where('agency_id', $agencyId));
            })
            ->distinct('branch_id')
            ->count('branch_id');

        $maintenanceAlerts = Subscription::query()
            ->where('status', 'active')
            ->where('end_date', '<', now())
            ->when($agencyId, function ($q) use ($agencyId) {
                $q->whereHas('branch', fn($b) => $b->where('agency_id', $agencyId));
            })
            ->distinct('branch_id')
            ->count('branch_id');

        return response()->json([
            'data' => [
                'total_branches' => $totalBranches,
                'total_branches_new_this_month' => $newThisMonth,
                'active_branches' => $activeBranches,
                'active_branches_percent' => $totalBranches
                    ? round(($activeBranches / $totalBranches) * 100)
                    : 0,
                'expiring_soon' => $expiringSoon,
                'expiring_soon_percent' => $totalBranches
                    ? round(($expiringSoon / $totalBranches) * 100)
                    : 0,
                'maintenance_alerts' => $maintenanceAlerts,
            ],
        ]);
    }
    public function paginate(array $payload)
    {
        $agencyId = $payload['agency_id'] ?? null;
        $search = $payload['search'] ?? null;
        $status = $payload['status'] ?? null;
        $perPage = $payload['per_page'] ?? 12;

        $branches = Branch::query()
            ->with(['location', 'agencies', 'services'])
            ->withCount(['rooms', 'patients', 'employees'])
            ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%")
                        ->orWhereHas('location', function ($loc) use ($search) {
                            $loc->where('city', 'like', "%{$search}%")
                                ->orWhere('street', 'like', "%{$search}%")
                                ->orWhere('province', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status && $status !== 'all', fn($q) => $q->where('status', $status))
            ->latest('created_at')
            ->paginate($perPage);

        $branches->getCollection()->transform(function ($branch) {
            return [
                'branch_id' => $branch->branch_id,
                'uuid' => $branch->uuid,
                'name' => $branch->name,
                'description' => $branch->description,
                'image' => $branch->image,
                'status' => $branch->status,
                'is_verified' => $branch->is_verified,
                'contact_number' => $branch->contact_number,
                'email' => $branch->email,
                'location' => $branch->location ? [
                    'street' => $branch->location->street,
                    'city' => $branch->location->city,
                    'province' => $branch->location->province,
                    'country' => $branch->location->country,
                ] : null,
                'agency' => $branch->agencies ? [
                    'agency_id' => $branch->agencies->agency_id,
                    'name' => $branch->agencies->name,
                ] : null,
                'services' => $branch->services->pluck('name'),
                'rooms_count' => $branch->rooms_count,
                'staff_count' => $branch->employees_count,
                'patients_count' => $branch->patients_count,
            ];
        });
        return $branches;
    }
}
