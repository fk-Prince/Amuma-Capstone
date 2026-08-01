<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Resources\ServiceResource;
use App\Models\EmployeeBranch;
use App\Repository\ServiceRepository;
use App\Models\User;
use App\Repository\CategoryRepository;
use App\Repository\EmployeeRepository;

use Exception;
use Illuminate\Support\Facades\DB;

class ServiceService
{
    private ServiceRepository $serviceRepository;
    private CategoryRepository $categoryRepository;
    private EmployeeRepository $employeeRepository;

    public function __construct(ServiceRepository $serviceRepository, CategoryRepository $categoryRepository,  EmployeeRepository $employeeRepository)
    {
        $this->serviceRepository = $serviceRepository;
        $this->categoryRepository = $categoryRepository;
        $this->employeeRepository = $employeeRepository;
    }

    public function createService(array $payload, User $user)
    {

        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
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
                'data'    => new ServiceResource($service->load('categories')),
            ], 201);
        });
    }
    public function updateService(array $payload, string $id, User $user)
    {

        return DB::transaction(function () use ($payload, $id, $user) {

            $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
            AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Services, PermissionAction::Update);

            $existingService = $this->serviceRepository->findOneByFields([
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
                'data'    => new ServiceResource($existingService->fresh()->load('categories')),
            ]);
        });
    }

    public function getBranchService(array $payload)
    {
        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);

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

    // public function retrieveService(array $payload, User $user)
    // {
    //     $branch = BranchGuard::resolveBranch( $payload['branch_uuid']);
    //     AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Services, PermissionAction::Read);

    //     $branch->load([
    //         'services.categories',
    //     ]);
    //     $services = $branch->services;

    //     if (!empty($payload['type'])) {
    //         $type = strtolower($payload['type'] ?? '');
    //         $services = $services->filter(function ($service) use ($type) {
    //             $serviceType = strtolower($service->type);
    //             return $serviceType === $type || $serviceType === 'both';
    //         });
    //     }

    //     return response()->json([
    //         'branch_uuid' => $branch->uuid,
    //         'branch_name' => $branch->name,
    //         'services' => $services->map(function ($service) {
    //             return [
    //                 'service_uuid' => $service->service_uuid,
    //                 'service_name' => $service->service_name,
    //                 'price_id' => $service->price_id,
    //                 'price' => $service->price,
    //                 'category' => $service->categories?->category_name,
    //                 'type' => $service->type,
    //                 'is_available' => $service->is_available,
    //             ];
    //         }),
    //     ]);
    // }
    public function retrieveService(array $payload, User $user)
    {
        $branch = BranchGuard::resolveBranch(

            $payload['branch_uuid']
        );

        AuthGuard::requireModule(
            $user,
            $branch->branch_id,
            ModuleEnum::Services,
            PermissionAction::Read
        );

        $branch->load([
            'services.categories',
        ]);

        $services = $branch->services;

        if (!empty($payload['type'])) {
            $type = strtolower($payload['type']);

            $services = $services->filter(function ($service) use ($type) {
                $serviceType = strtolower($service->type);

                return $serviceType === $type || $serviceType === 'both';
            });
        }

        return response()->json([
            'branch_uuid' => $branch->uuid,
            'branch_name' => $branch->name,
            'services' => $services
                ->values()
                ->map(function ($service) {
                    return [
                        'service_id' => $service->service_id,
                        'service_uuid' => $service->service_uuid,
                        'service_name' => $service->service_name,
                        'price' => $service->price,
                        'category_name' => $service->categories?->category_name,
                        'type' => $service->type,
                        'type_formatted' => match ($service->type) {
                            'facility' => 'In-house Services',
                            'online' => 'Homecare Services',
                            'both' => 'Homecare and In-house Services',
                            default => '',
                        },
                        'is_available' => $service->is_available,
                    ];
                }),
        ]);
    }

    // public function assignEmployeeService(User $user, array $payload)
    // {
    //     $branch = >findByField('uuid', $payload['branch_uuid']);
    //     if (!$branch)  throw new Exception(__('Branch does not exist'), 404);

    //     AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Services, PermissionAction::Create);

    //     foreach ($payload['employee_service'] as $item) {
    //         $employeeBranch = EmployeeBranch::where('employee_id', $item['employee_id'])
    //             ->where('branch_id', $branch->branch_id)
    //             ->first();

    //         if (!$employeeBranch) {
    //             continue;
    //         }

    //         $exists = $this->serviceRepository->existsEmployeeService($item['service_id'], $item['employee_id'], $branch->branch_id);
    //         if ($exists) {
    //             continue;
    //         }
    //         $payload = [
    //             'employee_branch_id' => $employeeBranch->employee_branch_id,
    //             'service_id' => $item['service_id'],
    //         ];

    //         $this->serviceRepository->assignEmployee($payload);
    //     };

    //     return response()->json([
    //         'message' => 'Successfully Assigned Services to Employee'
    //     ], 200);
    // }

    public function assignEmployeeService(User $user, array $payload)
    {
        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        AuthGuard::requireModule($user,  $branch->branch_id,  ModuleEnum::Services, PermissionAction::Create);

        foreach ($payload['employee_service'] as $item) {

            $employeeBranch = EmployeeBranch::where('employee_id', $item['employee_id'])
                ->where('branch_id', $branch->branch_id)
                ->first();

            if (!$employeeBranch) {
                continue;
            }

            if ($item['action'] === 'assign') {

                $exists = $this->serviceRepository->existsEmployeeService(
                    $item['service_id'],
                    $item['employee_id'],
                    $branch->branch_id
                );


                $this->serviceRepository->assignEmployee([
                    'employee_branch_id' => $employeeBranch->employee_branch_id,
                    'service_id' => $item['service_id'],
                ]);
            } elseif ($item['action'] === 'unassign') {

                $this->serviceRepository->unassignEmployee(
                    $employeeBranch->employee_branch_id,
                    $item['service_id']
                );
            }
        }

        return response()->json([
            'message' => 'Employee service assignments updated successfully.'
        ]);
    }
}
