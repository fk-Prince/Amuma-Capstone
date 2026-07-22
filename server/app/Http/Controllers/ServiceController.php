<?php

namespace App\Http\Controllers;

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
        return $this->serviceService->createService($request->all(), $request->user());
    }
    public function update(UpdateServiceRequest  $request, string $id)
    {
        return $this->serviceService->updateService($request->all(), $id, $request->user());
    }

    public function getBranchServices(Request $request, string $uuid)
    {
        return $this->serviceService->getBranchService(['branch_uuid' => $uuid, ...$request->all()]);
    }

    public function assignEmployee(Request $request)
    {
        return $this->serviceService->assignEmployeeService($request->user(), $request->all());
    }
}
