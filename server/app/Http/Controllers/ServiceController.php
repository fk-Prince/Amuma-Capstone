<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequest;
use App\Service\ServiceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    private ServiceService $serviceService;

    public function __construct(ServiceService $serviceService)
    {
        $this->serviceService = $serviceService;
    }

    public function store(ServiceRequest $request)
    {
        return $this->serviceService->createService($request->all(), $request->user());
    }

    // public function index(Request $request)
    // {
    //     return $this->serviceService->listService(
    //         $request->user(),
    //         $request->input('per_page', 15)
    //     );
    // }
    // public function show(Request $request, string $uuid)
    // {
    //     return $this->serviceService->getService($request->user(), $uuid);
    // }

    // public function update(Request $request, string $uuid)
    // {
    //     return $this->serviceService->updateService($request->user(), $uuid, $request->all());
    // }

    // public function destroy(Request $request, string $uuid)
    // {
    //     $this->serviceService->deleteService($request->user(), $uuid);
    //     return response()->json(['message' => 'Deleted successfully'], 200);
    // }

    // public function restore(Request $request, string $uuid)
    // {
    //     return $this->serviceService->restoreService($request->user(), $uuid);
    // }
}
