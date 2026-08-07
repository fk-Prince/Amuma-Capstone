<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Service\ServiceService;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    private ServiceService $serviceService;

    public function __construct(ServiceService $serviceService)
    {
        $this->serviceService = $serviceService;
    }

    public function store(StoreServiceRequest $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Services, PermissionAction::Create);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->serviceService->createService($request->all(), $request->user());
    }
    public function update(UpdateServiceRequest $request, string $id)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Services, PermissionAction::Update);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->serviceService->updateService($request->all(), $id, $request->user());
    }

    public function index(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Services, PermissionAction::Read);
        $request->merge([
            'branch' => $branch,
        ]);
        return $this->serviceService->retrieveService($request->all(),  $request->user());
    }

    public function getBranchServices(Request $request, string $uuid)
    {
        return $this->serviceService->getBranchService(['branch_uuid' => $uuid, ...$request->all()]);
    }

    public function assignEmployee(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(),  $branch->branch_id,  ModuleEnum::Services, PermissionAction::Create);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->serviceService->assignEmployeeService($request->user(), $request->all());
    }
}
