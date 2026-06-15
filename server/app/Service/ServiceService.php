<?php

namespace App\Service;

use App\Repository\ServiceRepository;
use App\Http\Resources\ServiceResource;
use App\Models\User;
use App\Repository\BranchRepository;
use App\Repository\CategoryRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceService
{
    private ServiceRepository $serviceRepository;
    private BranchRepository $branchRepository;
    private CategoryRepository $categoryRepository;
    private PriceService $priceService;

    public function __construct(ServiceRepository $serviceRepository, BranchRepository $branchRepository, CategoryRepository $categoryRepository, PriceService $priceService)
    {
        $this->serviceRepository = $serviceRepository;
        $this->branchRepository = $branchRepository;
        $this->categoryRepository = $categoryRepository;
        $this->priceService = $priceService;
    }

    public function createService(array $payload, User $user)
    {
        $user->load('roles');

        $hasRole = $user->roles->contains(
            fn($role) => in_array($role->role_type, ['branch_owner', 'administrator'])
        );

        if (!$hasRole) {
            abort(403, 'Unauthorized');
        }

        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

        if (!$branch) {
            return response()->json([
                'message' => 'Branch does not exist'
            ], 404);
        }

        $existingService = $this->serviceRepository->existsInBranch($branch->branch_id, 'service_name', $payload['service_name']);
        if ($existingService) {
            return response()->json([
                'success' => false,
                'message' => 'Service already exists in this branch',
            ], 409);
        }

        return DB::transaction(function () use ($payload, $branch) {

            $categoryData = !empty($payload['category_id'])
                ? ['category_id' => $payload['category_id'], 'branch_id' => $branch->branch_id]
                : ['category_name' => $payload['category_name'], 'branch_id' => $branch->branch_id];

            $category = $this->categoryRepository->create($categoryData);

            $price_id = $this->priceService->createPrice($payload['price']);

            $service = $this->serviceRepository->create([
                'category_id'      => $category->category_id,
                'price_id'         => $price_id,
                'branch_id'        => $branch->branch_id,
                'service_name'     => $payload['service_name'],
                'maximum_duration' => $payload['maximum_duration'],
                'is_available'     => $payload['is_available'] ?? true,
                'type'             => $payload['type'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Service created successfully',
                'data'    => $service
            ], 201);
        });
    }
}
