<?php

namespace App\Http\Controllers;

use App\Service\MedicationService;
use Illuminate\Http\Request;

class MedicationController extends Controller
{
    private MedicationService $medicationService;

    public function __construct(MedicationService $medicationService)
    {
        $this->medicationService = $medicationService;
    }

    public function store(Request $request)
    {
        return $this->medicationService->createMedication($request->user(), $request->all());
    }

    public function update(Request $request, string $id)
    {
        return $this->medicationService->updateMedication($request->user(), $request->all(), $id);
    }
}
