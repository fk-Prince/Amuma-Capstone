<?php

namespace App\Service;

use App\Repository\BranchRepository;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Models\User;
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

    public function getBranches(array $payload)
    {
        $branch = $this->branchRepository->getHighestReviewPaginate($payload['per_page']);
        return BranchResource::collection($branch);
    }
    public function getBranchesByFilter(array $payload)
    {
        if (!empty($payload['lat']) && !empty($payload['long'])) {
            $city = $this->nomaticeService->getCityByCords($payload['lat'], $payload['long']);
        } elseif (!empty($payload['city'])) {
            $city = $payload['city'];
        } else {
            $position = Location::get(request()->ip());
            $city = $position?->cityName;
        }
        $filters = [
            'city' => $city,
            'name' => $payload['name'],
            'plan_name' => $payload['plan_name']
        ];

        $branch = $this->branchRepository->getFilterBranches($payload['per_page'], $filters);
        return BranchResource::collection($branch);
    }
}
