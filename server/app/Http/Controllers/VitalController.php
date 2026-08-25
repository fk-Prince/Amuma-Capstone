<?php

namespace App\Http\Controllers;

use App\Service\VitalService;
use Illuminate\Http\Request;

class VitalController extends Controller
{
    public function __construct(
        private VitalService $vitalService
    ) {}

    public function index(Request $request)
    {
        return $this->vitalService->listVitals($request->all());
    }

    public function store(Request $request)
    {
        return $this->vitalService->createVital($request->user(), $request->all());
    }

    public function update(Request $request, string $id)
    {
        return $this->vitalService->updateVital($request->user(), $request->all(), $id);
    }
}
