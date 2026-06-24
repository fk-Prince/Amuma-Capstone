<?php

namespace App\Service;

use App\Repository\BranchRepository;
use App\Http\Resources\BranchResource;
use App\Models\User;
use App\Service\Utils\AuthGuard;
use App\Service\Utils\NominatimService;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;

class BranchService
{
    private BranchRepository $branchRepository;
    private NominatimService $nomaticeService;

    public function __construct(BranchRepository $branchRepository, NominatimService $nomaticeService)
    {
        $this->branchRepository = $branchRepository;
        $this->nomaticeService = $nomaticeService;
    }

    public function getFeaturedBranches(array $payload)
    {
        $branch = $this->branchRepository->getHighestReviewPaginate($payload['per_page']);
        return BranchResource::collection($branch);
    }

    public function getBranchesByFilter(array $payload)
    {
        if (!empty($payload['lat']) && !empty($payload['long'])) {
            $city = $this->nomaticeService->getCityByCords($payload['lat'], $payload['long']);
        } elseif (!empty($payload['location'])) {
            $city = $payload['location'];
        }
        // else {
        //     // $position = Location::get('8.8.8.8'); // TEST IP
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
}
