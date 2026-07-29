<?php

namespace App\Http\Controllers;

use App\Http\Requests\Bed\StoreBedRequest;
use App\Http\Requests\Bed\UpdateBedRequest;
use App\Service\BedService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BedController extends Controller
{
    private BedService $bedService;

    public function __construct(BedService $bedService)
    {
        $this->bedService = $bedService;
    }

    public function store(StoreBedRequest $request)
    {
        return $this->bedService->createBed($request->user(), $request->all());
    }

    public function update(UpdateBedRequest $request, string $id)
    {
        return $this->bedService->updateBed($request->user(), $request->all(), $id);
    }
}
