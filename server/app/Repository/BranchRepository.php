<?php

namespace App\Repository;

use App\Enums\RoleEnum;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\BranchImage;
use App\Models\BranchSubscription;
use App\Models\EmployeeBranch;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\DB;

class BranchRepository
{

    public function create(array $payload)
    {
        return Branch::create($payload);
    }

    public function getUserBrancheses()
    {
        return Branch::all();
    }

    // public function getUserBranchesesByField(string $column, string $value)
    // {
    //     return Branch::all();
    // }


    public function findByField(string $column, string $value)
    {
        return Branch::where($column, $value)->first();
    }

    public function mergeSetting(Branch $branch, string $key, array $values): void
    {
        DB::update(
            "UPDATE branches SET settings = (
                COALESCE(settings::jsonb, '{}'::jsonb)
                || jsonb_build_object(?::text, COALESCE(settings::jsonb -> ?::text, '{}'::jsonb) || ?::jsonb)
            )::json WHERE branch_id = ?",
            [$key, $key, json_encode($values), $branch->branch_id]
        );

        $branch->refresh();
    }

    public function putSetting(Branch $branch, string $key, mixed $value): void
    {
        DB::update(
            "UPDATE branches SET settings = (
                COALESCE(settings::jsonb, '{}'::jsonb) || jsonb_build_object(?::text, ?::jsonb)
            )::json WHERE branch_id = ?",
            [$key, json_encode($value), $branch->branch_id]
        );

        $branch->refresh();
    }

    public function setupCounts(Branch $branch): array
    {
        $contracts = $branch->contracts()
            ->where('is_active', true)
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        return [
            'staff' => EmployeeBranch::where('branch_id', $branch->branch_id)
                ->where('status', '!=', EmployeeBranch::STATUS_INACTIVE)
                ->whereRaw("LOWER(REPLACE(role_name, ' ', '_')) <> ?", [RoleEnum::AgencyOwner->value])
                ->count(),
            'services' => $branch->services()->count(),
            'rooms' => $branch->rooms()->whereHas('beds')->count(),
            'homecare_plans' => (int) ($contracts['Homecare'] ?? 0),
            'facility_plans' => (int) ($contracts['Facility'] ?? 0),
        ];
    }

    public function getHighestReviewPaginate(int $perPage)
    {
        return Branch::with([
            'subscriptions.plans',
            'reviews',
            'location',
            'contracts',
        ])
            ->where('status', Branch::STATUS_VERIFIED)
            ->onLivePlan()
            ->setupComplete()
            ->whereHas('agencies', function ($q) {
                $q->where('status', Branch::STATUS_VERIFIED);
            })
            ->withAvg('reviews', 'rate')
            ->withMin(['services as homecare_service_min_price' => fn($query) => $query
                ->whereIn('type', ['online', 'both'])
                ->where('is_available', true)], 'price')
            ->withCount([
                'bookings' => fn($query) => $query->where('status', Booking::STATUS_APPROVED),
                'patients',
            ])
            ->tap(fn($query) => $this->prioritizeActiveSubscription($query))
            ->orderByRaw('reviews_avg_rate DESC NULLS LAST')
            ->orderByDesc('bookings_count')
            ->orderByDesc('patients_count')
            ->latest()
            ->paginate($perPage);
    }


    public function getUserBranches(array $branchIds)
    {
        return  Branch::with([
            'location',
            'subscriptions.plans',
            'agencies.locations',
            'agencies.registrant.employee',
            'agencies.registrant.client',
            'agencies.registrant.systemOwner',
            'subscriptionLink.latestRejection',
            'subscriptionLink.subscription' => fn($query) => $query
                ->withCount([
                    'branchLinks as branches_used' => fn($links) => $links
                        ->where('status', '!=', BranchSubscription::STATUS_REJECTED)
                        ->where('type', BranchSubscription::TYPE_INCLUDED),
                ])
                ->withExists([
                    'payments as has_paid_payment' => fn($payments) => $payments
                        ->where('status', SubscriptionPayment::STATUS_PAID),
                ]),
        ])
            ->whereIn('branch_id', $branchIds)
            ->get()
            ->keyBy('branch_id');
    }

    public function getFilterBranches(
        int $perPage,
        array $filters,
        string $sort = 'recommended',
        ?float $lat = null,
        ?float $long = null,
        ?array $viewerOrigin = null
    ) {
        $query = Branch::with([
            'subscriptions.plans',
            'reviews',
            'location',
            'contracts',
        ])
            ->where('status', Branch::STATUS_VERIFIED)
            ->onLivePlan()
            ->setupComplete()
            ->whereHas('agencies', function ($q) {
                $q->where('status', Branch::STATUS_VERIFIED);
            })
            ->withAvg('reviews', 'rate')
            ->withMin(['services as homecare_service_min_price' => fn($query) => $query
                ->whereIn('type', ['online', 'both'])
                ->where('is_available', true)], 'price')
            ->withCount('reviews')
            ->withCount('bookings')

            ->when(!empty($filters['city']), function ($query) use ($filters) {
                $search = trim($filters['city']);

                $query->whereHas('location', function ($q) use ($search) {
                    $q->where(function ($match) use ($search) {
                        foreach (['street', 'city', 'province', 'full_address'] as $column) {
                            $match->orWhere($column, 'ILIKE', '%' . $search . '%');
                        }

                        foreach (['city', 'province'] as $column) {
                            $match->orWhereRaw(
                                "{$column} <> '' AND ? ILIKE '%' || {$column} || '%'",
                                [$search]
                            );
                        }
                    });
                });
            })
            ->when(!empty($filters['provider_name']), function ($query) use ($filters) {
                $search = '%' . trim($filters['provider_name']) . '%';

                $query->where(fn($match) => $match
                    ->where('branches.name', 'ILIKE', $search)
                    ->orWhereHas('agencies', fn($agency) => $agency->where('name', 'ILIKE', $search))
                    ->orWhereHas('services', fn($service) => $service
                        ->whereIn('type', ['online', 'both'])
                        ->where('is_available', true)
                        ->where('service_name', 'ILIKE', $search)));
            })

            ->when(
                !empty($filters['plan_code']) && $filters['plan_code'] !== 'C',
                function ($query) use ($filters) {
                    $code = in_array($filters['plan_code'], [Plan::CODE_HOMECARE, Plan::CODE_FACILITY], true)
                        ? $filters['plan_code']
                        : Plan::CODE_HYBRID;

                    $query->whereRaw(BranchSubscription::hasPlanCodeSql("'{$code}', '" . Plan::CODE_HYBRID . "'"));
                }
            );

        $this->prioritizeActiveSubscription($query);
        $this->prioritizeStartingPrice($query);

        $nearestOrigin = $lat !== null && $long !== null ? [$lat, $long] : $viewerOrigin;

        $origin = match ($sort) {
            'nearest' => $nearestOrigin,
            'recommended' => $viewerOrigin,
            default => null,
        };

        if ($origin !== null) {
            $this->orderByDistance($query, ...$origin);
        }

        if ($sort === 'highest_rated') {
            $query->orderByDesc('reviews_avg_rate');
        } elseif ($sort === 'most_popular') {
            $query->orderByDesc('bookings_count');
        } elseif ($sort !== 'nearest' || $origin === null) {
            $query->orderByDesc('reviews_avg_rate')
                ->orderByDesc('bookings_count')
                ->orderByDesc('reviews_count');
        }

        return $query->paginate($perPage);
    }

    private function orderByDistance($query, float $lat, float $long): void
    {
        $distanceExpr = 'CASE
            WHEN locations.latitude IS NULL OR locations.longitude IS NULL THEN NULL
            ELSE (6371 * acos(LEAST(1, GREATEST(-1,
                cos(radians(?)) * cos(radians(locations.latitude)) * cos(radians(locations.longitude) - radians(?))
                + sin(radians(?)) * sin(radians(locations.latitude))
            ))))
        END';

        $query
            ->leftJoin('locations', 'locations.location_id', '=', 'branches.location_id')
            ->selectRaw("{$distanceExpr} as distance_km", [$lat, $long, $lat])
            ->orderByRaw(
                "({$distanceExpr}) IS NULL, ({$distanceExpr}) ASC",
                [$lat, $long, $lat, $lat, $long, $lat]
            );
    }

    private function prioritizeActiveSubscription($query): void
    {
        $query->orderByRaw("CASE WHEN EXISTS (
            SELECT 1 FROM branch_subscription bs
            JOIN subscriptions s ON s.subscription_id = bs.subscription_id
            WHERE bs.branch_id = branches.branch_id
              AND bs.status = ?
              AND bs.active_until IS NULL
              AND s.status = ?
              AND s.end_date >= CURRENT_DATE
        ) THEN 0 ELSE 1 END", [
            BranchSubscription::STATUS_APPROVED,
            Subscription::STATUS_ACTIVE,
        ]);
    }

    private function prioritizeStartingPrice($query): void
    {
        $hasPlan = fn(string $codes) => BranchSubscription::hasPlanCodeSql($codes);

        $query->orderByRaw("(
            ({$hasPlan("'A', 'C'")} AND EXISTS (
                SELECT 1 FROM services sv
                WHERE sv.branch_id = branches.branch_id
                    AND sv.type IN ('online', 'both')
                    AND sv.is_available = true
            ))
            OR
            ({$hasPlan("'B', 'C'")} AND EXISTS (
                SELECT 1 FROM branch_contracts bc
                WHERE bc.branch_id = branches.branch_id
                    AND bc.category = 'Facility'
                    AND bc.is_active = true
            ))
        ) DESC");
    }

    public function getBranch(string $uuid)
    {
        return Branch::with([
            'subscriptions.plans',
            'location',
            'contracts',
            'agencies',
            'services.categories',
            'bookings',
            'rooms',
            'images' => function ($query) {
                $query->public()->whereIn('type', [
                    BranchImage::IMAGE_BRANCH,
                    BranchImage::IMAGE_COMMON_ROOM,
                    BranchImage::IMAGE_VIP_ROOM,
                    BranchImage::IMAGE_OTHER,
                    BranchImage::IMAGE_COVER,
                ]);
            },
        ])
            ->withAvg('reviews', 'rate')
            ->withMin('diagnosisCases as diagnosis_case_min_price', 'price')
            ->withMax('diagnosisCases as diagnosis_case_max_price', 'price')
            ->withCount('reviews')
            ->where('uuid', $uuid)
            ->first();
    }

    public function getBranchHealthProviders(string $uuid)
    {
        return EmployeeBranch::query()
            ->select(['employee_branch_id', 'employee_id', 'role_name'])
            ->whereHas('branches', fn($query) => $query->where('uuid', $uuid))
            ->whereIn('role_name', [RoleEnum::Nurse->value, RoleEnum::Caregiver->value])
            ->where('status', '!=', EmployeeBranch::STATUS_INACTIVE)
            ->with('employees:employee_id,first_name,last_name,avatar,achievements')
            ->get();
    }
}