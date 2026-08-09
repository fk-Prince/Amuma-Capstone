<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Repository\BranchRepository;
use App\Http\Resources\BranchResource;
use App\Models\BranchImage;
use App\Models\User;
use App\Service\External\SupabaseService;
use App\Service\Geo\NominatimService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        // $branch = $this->branchRepository->getBranch($uuid);

        // $branch->averageRating = round($branch->reviews_avg_rate ?? 0, 1);
        // $branch->reviewCount = $branch->reviews_count ?? 0;

        // $settings = (array) ($branch->settings ?? []);

        // $branch->settings = array_merge($settings, [
        //     'adl_hourly_rate' => 500,
        //     'adl_min_hour' => 8,
        // ]);

        // return $branch;
        $branch = $this->branchRepository->getBranch($uuid);
        return new BranchResource($branch);
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
            $city = $this->nomaticeService->getCityByCoords($payload['lat'], $payload['long']);
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



    public function updateBranch(array $payload)
    {

        return DB::transaction(function () use ($payload) {
            $branch = $payload['branch'];
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

    public function action(array $payload)
    {

        if ($payload['action'] === 'image') {

            if (empty($payload['image'])) {
                throw new \InvalidArgumentException('Image is required.');
            }
            $stored = SupabaseService::store($payload['image']);

            if (empty($stored['url'])) {
                throw new \RuntimeException('Image upload failed.');
            }

            $data = BranchImage::create([
                'image_url' => $stored['url'],
                'branch_id' => $payload['branch_id'],
                'type' => $payload['type'],
                'description' => $payload['description'] ?? '',
            ]);

            return response()->json([
                'message' => 'Branch Image have been successfully added.',
                'data' => $data
            ], 200);
        }
    }

    public function retrieveAction(array $payload)
    {
        if ($payload['action'] === 'image') {
            $perPage = $payload['per_page'] ?? 20;
            return BranchImage::where('branch_id', $payload['branch_id'])
                ->latest()
                ->paginate($perPage);
        }
    }


    public function updateSettings(array $payload)
    {
        $branch = $payload['branch'];

        $settingPayload = Arr::only($payload, [
            'reserved_walkin_slots',
            'enable_booking_pre_admission',
            'enable_booking_complete_admission',
            'minimum_adl_hours',
            'is_open',
            'time_zone',
            'opening',
            'closing',
            'currency',
        ]);

        $branch->update([
            'settings' => $settingPayload,
        ]);

        return [
            'message' => 'Branch settings updated successfully.',
        ];
    }
}
