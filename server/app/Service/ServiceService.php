<?php

namespace App\Service;

use App\Repository\ServiceRepository;
use App\Http\Resources\ServiceResource;
use App\Models\User;
use App\Repository\BranchRepository;
use App\Repository\CategoryRepository;
use App\Service\Utils\AuthGuard;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceService
{
    private ServiceRepository $serviceRepository;
    private BranchRepository $branchRepository;
    private CategoryRepository $categoryRepository;
    private PriceService $priceService;

    public function __construct(ServiceRepository $serviceRepository, BranchRepository $branchRepository, CategoryRepository $categoryRepository)
    {
        $this->serviceRepository = $serviceRepository;
        $this->branchRepository = $branchRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function createService(array $payload, User $user)
    {
        AuthGuard::requireRole($user, ['branch_owner', 'administrator']);

        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

        if (!$branch) {
            throw new Exception(__('Branch does not exist'), 404);
        }

        $existingService = $this->serviceRepository->existsInBranch(
            $branch->branch_id,
            'service_name',
            $payload['service_name']
        );

        if ($existingService) {
            throw new Exception(__('Service already exists in this branch'), 409);
        }

        if (!isset($payload['price']) || $payload['price'] <= 0) {
            throw new Exception(__('Invalid price.'), 422);
        }

        return DB::transaction(function () use ($payload, $branch) {

            $category = $this->categoryRepository->findByFields([
                ['branch_id', '=', $branch->branch_id],
                ['category_name', '=', $payload['category_name']],
            ]);

            if (!$category) {
                $category = $this->categoryRepository->create([
                    'category_name' => $payload['category_name'],
                    'branch_id' => $branch->branch_id,
                ]);
            }

            $service = $this->serviceRepository->create([
                'category_id'      => $category->category_id,
                'price'            => $payload['price'],
                'branch_id'        => $branch->branch_id,
                'service_name'     => $payload['service_name'],
                'maximum_duration' => $payload['maximum_duration'],
                'is_available'     => $payload['is_available'] ?? true,
                'type'             => $payload['type'],
            ]);

            return response()->json([
                'message' => 'Service created successfully',
                'data'    => $service,
            ], 201);
        });
    }
    public function updateService(array $payload, string $id, User $user)
    {
        AuthGuard::requireRole($user, ['branch_owner', 'administrator']);

        return DB::transaction(function () use ($payload, $id) {

            $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

            if (!$branch) {
                throw new Exception(__('Branch does not exist'), 404);
            }

            $existingService = $this->serviceRepository->findByFields([
                ['branch_id', '=', $branch->branch_id],
                ['service_id', '=', $id],
            ]);

            if (!$existingService) {
                throw new Exception(__('Service dont exists.'), 409);
            }

            if (!isset($payload['price']) || $payload['price'] <= 0) {
                throw new Exception(__('Invalid price.'), 422);
            }

            $category = $this->categoryRepository->findByFields([
                ['branch_id', '=', $branch->branch_id],
                ['category_name', '=', $payload['category_name']],
            ]);

            if (!$category) {
                $category = $this->categoryRepository->create([
                    'category_name' => $payload['category_name'],
                    'branch_id' => $branch->branch_id,
                ]);
            }

            $existingService->update([
                'category_id'      => $category->category_id,
                'price'            => $payload['price'],
                'service_name'     => $payload['service_name'],
                'maximum_duration' => $payload['maximum_duration'],
                'is_available'     => $payload['is_available'] ?? true,
                'type'             => $payload['type'],
            ]);

            return response()->json([
                'message' => 'Service updated successfully',
                'data' => $existingService->fresh(),
            ]);
        });
    }

    public function getBranchService(array $payload)
    {
        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);
        if (!$branch)  throw new Exception(__('Branch does not exist'), 404);

        $branch->load([
            'services.categories',
        ]);

        return response()->json([
            'branch_uuid' => $branch->uuid,
            'branch_name' => $branch->name,
            'services' => $branch->services->map(function ($service) {
                return [
                    'service_id' => $service->service_id,
                    'service_uuid' => $service->service_uuid,
                    'service_name' => $service->service_name,
                    'price' => $service->price,
                    'category_name' => $service->categories?->category_name,
                    'type' => $service->type,
                    'maximum_duration' => date('H:i:s', strtotime($service->maximum_duration)),
                    'is_available' => $service->is_available,
                ];
            }),
        ]);
    }

    public function retrieveService(array $payload, User $user)
    {
        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);
        if (!$branch)  throw new Exception(__('Branch does not exist'), 404);

        $branch->load([
            'services.categories',
            'services.price'
        ]);

        return response()->json([
            'branch_uuid' => $branch->uuid,
            'branch_name' => $branch->name,
            'services' => $branch->services->map(function ($service) {
                return [
                    'service_uuid' => $service->service_uuid,
                    'service_name' => $service->service_name,
                    'price_id' => $service->price_id,
                    'price' => $service->price,
                    'category' => $service->categories?->category_name,
                    'type' => $service->type,
                    'is_available' => $service->is_available,
                ];
            }),
        ]);
    }
}
