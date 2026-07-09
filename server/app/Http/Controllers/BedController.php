<?php

namespace App\Http\Controllers;

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

    public function store(Request $request)
    {
        return $this->bedService->createBed($request->user(), $request->all());
    }
}
