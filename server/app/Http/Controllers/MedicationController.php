<?php

namespace App\Http\Controllers;

use App\Service\MedicationService;
use Illuminate\Http\Request;

class MedicationController extends Controller
{

    public function __construct(private MedicationService $medicationService) {}

    public function index(Request $request)
    {
        return $this->medicationService->listMedications($request->all());
    }

    public function store(Request $request)
    {
        return $this->medicationService->createMedication($request->user(), $request->all());
    }

    public function update(Request $request, string $id)
    {
        return $this->medicationService->updateMedication($request->user(), $request->all(), $id);
    }

    public function dosage(Request $request)
    {
        return $this->medicationService->markDosage($request->user(), $request->all());
    }
}
