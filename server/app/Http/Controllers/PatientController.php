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
        return $this->patientService->retrievePatients($request->all(), $request->user());
    }

    public function show(Request $request, string $uuid)
    {
        return $this->patientService->showPatient($request->all(), $request->user(), $uuid);
    }
    // public function medication(Request $request)
    // {
    //     return $this->patientService->saveMedication($request->all(), $request->user());
    // }
}
