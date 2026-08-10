<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Requests\Auth\StoreEmployeeRequest;
use App\Http\Requests\Auth\UpdateEmployeeRequest;
use App\Service\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmployeeController extends Controller
{
    private EmployeeService $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    public function store(StoreEmployeeRequest $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::EmployeeManagement, PermissionAction::Create);
        $request->merge([
            'branch_id' => $branch->branch_id,
            'branch' => $branch
        ]);
        return $this->employeeService->createEmployee($request->all(), $request->user());
    }

    public function update(UpdateEmployeeRequest $request, string $uuid)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::EmployeeManagement, PermissionAction::Create);
        $request->merge([
            'branch_id' => $branch->branch_id,
            'branch' => $branch
        ]);
        return $this->employeeService->updateEmployee($request->all(), $uuid, $request->user());
    }

    public function index(Request $request)
    {
        $type = $request->input('type', 'regular');
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        $request->merge([
            'branch_id' => $branch->branch_id,
            'branch' => $branch
        ]);
        if ($type === 'regular') {
            AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::EmployeeManagement, PermissionAction::Read);
        } else if ($type === 'schedule') {
            // AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Patients, PermissionAction::Create); // assign
        } else if ($type === 'service') {
            AuthGuard::requireModule($request->user(),  $branch->branch_id, ModuleEnum::Services, PermissionAction::Create);
        }
        return $this->employeeService->getEmployees($request->all(), $request->user(),   $type);
    }
}
