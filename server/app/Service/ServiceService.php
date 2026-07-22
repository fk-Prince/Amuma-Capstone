<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Models\EmployeeBranch;
use App\Repository\ServiceRepository;
use App\Models\User;
use App\Repository\BranchRepository;
use App\Repository\CategoryRepository;
use App\Repository\EmployeeRepository;
use App\Service\Utils\AuthGuard;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as FacadesLog;
use Resend\Log;

class ServiceService
{
    private ServiceRepository $serviceRepository;
    private BranchRepository $branchRepository;
    private CategoryRepository $categoryRepository;
    private EmployeeRepository $employeeRepository;

    public function __construct(ServiceRepository $serviceRepository, BranchRepository $branchRepository, CategoryRepository $categoryRepository,  EmployeeRepository $employeeRepository)
    {
        $this->serviceRepository = $serviceRepository;
        $this->branchRepository = $branchRepository;
        $this->categoryRepository = $categoryRepository;
        $this->employeeRepository = $employeeRepository;
    }

    public function createService(array $payload, User $user)
    {

        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

        if (!$branch) {
            throw new Exception(__('Branch does not exist'), 404);
        }
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Services, PermissionAction::Create);

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

        return DB::transaction(function () use ($payload, $id, $user) {

            $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

            if (!$branch) {
                throw new Exception(__('Branch does not exist'), 404);
            }
            AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Services, PermissionAction::Update);

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
                    'type_formatted' => $service->type === 'online'
                        ? 'Homecare Services'
                        : ($service->type == 'both' ? 'Homecare and Inhouse Services' : 'Inhouse Services'),
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

        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Services, PermissionAction::Read);

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

    public function assignEmployeeService(User $user, array $payload)
    {
        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);
        if (!$branch)  throw new Exception(__('Branch does not exist'), 404);

        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Services, PermissionAction::Create);

        foreach ($payload['employee_service'] as $item) {
            $employeeBranch = EmployeeBranch::where('employee_id', $item['employee_id'])
                ->where('branch_id', $branch->branch_id)
                ->first();

            if (!$employeeBranch) {
                continue;
            }

            $exists = $this->serviceRepository->existsEmployeeService($item['service_id'], $item['employee_id'], $branch->branch_id);
            if ($exists) {
                continue;
            }
            $payload = [
                'employee_branch_id' => $employeeBranch->employee_branch_id,
                'service_id' => $item['service_id'],
            ];

            $this->serviceRepository->assignEmployee($payload);
        };

        return response()->json([
            'message' => 'Successfully Assigned Services to Employee'
        ], 200);
    }
}
