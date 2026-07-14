<?php

namespace App\Http\Controllers;

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
        return $this->employeeService->createEmployee($request->all(), $request->user());
    }

    public function update(UpdateEmployeeRequest $request, string $uuid)
    {
        return $this->employeeService->updateEmployee($request->all(), $uuid, $request->user());
    }

    public function index(Request $request)
    {
        return $this->employeeService->getEmployees($request->all(), $request->user());
    }
}
