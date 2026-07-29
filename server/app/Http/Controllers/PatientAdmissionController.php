<?php

namespace App\Http\Controllers;

use App\Service\PatientAdmissionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PatientAdmissionController extends Controller
{
    private PatientAdmissionService $patientAdmissionService;

    public function __construct(PatientAdmissionService $patientAdmissionService)
    {
        $this->patientAdmissionService = $patientAdmissionService;
    }

    public function index(Request $request)
    {
        return $this->patientAdmissionService->listPatientAdmission(
            $request->user(), 
            $request->input('per_page', 15)
        );
    }

    public function store(Request $request)
    {
        return $this->patientAdmissionService->createPatientAdmission($request->user(), $request->all());
    }

    public function show(Request $request, string $uuid)
    {
        return $this->patientAdmissionService->getPatientAdmission($request->user(), $uuid);
    }

    public function update(Request $request, string $uuid)
    {
        return $this->patientAdmissionService->updatePatientAdmission($request->user(), $uuid, $request->all());
    }

    public function destroy(Request $request, string $uuid)
    {
        $this->patientAdmissionService->deletePatientAdmission($request->user(), $uuid);
        return response()->json(['message' => 'Deleted successfully'], 200);
    }
    
    public function restore(Request $request, string $uuid)
    {
        return $this->patientAdmissionService->restorePatientAdmission($request->user(), $uuid);
    }
}