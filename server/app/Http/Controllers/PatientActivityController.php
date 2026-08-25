<?php

namespace App\Http\Controllers;

use App\Service\PatientActivityService;
use Illuminate\Http\Request;

class PatientActivityController extends Controller
{
    public function __construct(
        private PatientActivityService $patientActivityService
    ) {}

    public function index(Request $request)
    {
        return $this->patientActivityService->listPatientActivities($request->all());
    }

    public function store(Request $request)
    {
        return $this->patientActivityService->createPatientActivity($request->user(), $request->all());
    }

    public function update(Request $request, string $id)
    {
        return $this->patientActivityService->updatePatientActivity($request->user(), $request->all(), $id);
    }
}
