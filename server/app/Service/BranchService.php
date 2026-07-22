<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Repository\BranchRepository;
use App\Http\Resources\BranchResource;
use App\Models\User;
use App\Service\Utils\AuthGuard;
use App\Service\Utils\NominatimService;
use App\Service\Utils\SupabaseService;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class BranchService
{
    private BranchRepository $branchRepository;
    private NominatimService $nomaticeService;

    public function __construct(BranchRepository $branchRepository, NominatimService $nomaticeService)
    {
        $this->branchRepository = $branchRepository;
        $this->nomaticeService = $nomaticeService;
    }

    public function getBranch(string $uuid)
    {
        $branch = $this->branchRepository->getBranch($uuid);

        $branch->averageRating = round($branch->reviews_avg_rate ?? 0, 1);
        $branch->reviewCount = $branch->reviews_count ?? 0;

        $settings = (array) ($branch->settings ?? []);

        $branch->settings = array_merge($settings, [
            'adl_hourly_rate' => 500,
            'adl_min_hour' => 8,
        ]);

        return $branch;
    }

    public function getFeaturedBranches(array $payload)
    {
        $branch = $this->branchRepository->getHighestReviewPaginate($payload['per_page']);
        return BranchResource::collection($branch);
    }

    public function getBranchesByFilter(array $payload)
    {
        $city = null;
        if (!empty($payload['lat']) && !empty($payload['long'])) {
            $city = $this->nomaticeService->getCityByCords($payload['lat'], $payload['long']);
        } elseif (!empty($payload['location'])) {
            $city = $payload['location'];
        }
        // else {
        //     $position = Location::get('8.8.8.8'); // TEST IP
        //     // // $position = Location::get(request()->ip()); // REAL IP
        //     // $city = $position?->cityName;
        //     $city = "Davao City";
        // }
        $filters = [
            'city' => $city,
            'provider_name' => $payload['provider_name'],
            'plan_code' => $payload['plan_code']
        ];


        $branch = $this->branchRepository->getFilterBranches($payload['per_page'], $filters);
        return BranchResource::collection($branch);
    }

    public function updateBranch(array $payload, User $user, string $branchUuid)
    {
        $branch = $this->branchRepository->findByField('uuid', $branchUuid);

        if (!$branch) {
            throw new Exception(__('Branch does not exist'), 404);
        }

        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::BranchSettings, PermissionAction::Update);

        return DB::transaction(function () use ($branch, $payload) {
            if (
                isset($payload['image']) &&
                $payload['image'] instanceof UploadedFile
            ) {
                $image = SupabaseService::store($payload['image']);
            }

            $branch->update([
                'name' => $payload['name'],
                'description' => $payload['description'],
                'contact_number' => $payload['contact_number'] ?? null,
                'image' => $image['url'] ?? $payload['image'] ?? null,
                'settings' => $payload['settings'] ?? null,
            ]);



            $branch->location()->updateOrCreate(
                [],
                [
                    'street' => $payload['location']['street'],
                    'city' => $payload['location']['city'],
                    'province' => $payload['location']['province'],
                    'country' => $payload['location']['country'],
                    'longitude' => $payload['location']['longitude'] ?? null,
                    'latitude' => $payload['location']['latitude'] ?? null,
                ]
            );

            return response()->json([
                'message' => 'Branch Information Successfully Updated.'
            ], 200);
        });
    }
}
