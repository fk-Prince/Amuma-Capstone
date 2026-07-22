<?php

namespace App\Http\Controllers;

use App\Service\PatientService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PatientController extends Controller
{
    private PatientService $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;
    }

    public function index(Request $request)
    {
        return $this->patientService->listPatient(
            $request->user(), 
            $request->input('per_page', 15)
        );
    }

    public function store(Request $request)
    {
        return $this->patientService->createPatient($request->user(), $request->all());
    }

    public function show(Request $request, string $uuid)
    {
        return $this->patientService->getPatient($request->user(), $uuid);
    }

    public function update(Request $request, string $uuid)
    {
        return $this->patientService->updatePatient($request->user(), $uuid, $request->all());
    }

    public function destroy(Request $request, string $uuid)
    {
        $this->patientService->deletePatient($request->user(), $uuid);
        return response()->json(['message' => 'Deleted successfully'], 200);
    }
    
    public function restore(Request $request, string $uuid)
    {
        return $this->patientService->restorePatient($request->user(), $uuid);
    }
}