<?php

namespace App\Http\Controllers;

use App\Service\PatientAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PatientAccessController extends Controller
{
    private PatientAccessService $patientAccessService;

    public function __construct(PatientAccessService $patientAccessService)
    {
        $this->patientAccessService = $patientAccessService;
    }

    public function retrieveAction(Request $request)
    {
        $clientId = $request->user()->client?->client_id;

        $payload = array_merge($request->all(), [
            'client_id' => $clientId,
            'user_id' => $request->user()->user_id,
        ]);

        if ($request->action === 'overview') {
            return $this->patientAccessService->overview($payload);
        }

        if ($request->action === 'schedule') {
            return $this->patientAccessService->scheduleList($payload);
        }

        if ($request->action === 'bookings') {
            return $this->patientAccessService->bookings($payload);
        }
    }

    public function executeAction(Request $request)
    {
        $clientId = $request->user()->client?->client_id;

        $payload = array_merge($request->all(), [
            'client_id' => $clientId,
        ]);

        if ($request->action === 'book_again') {
            return $this->patientAccessService->bookAgain($payload, $request->user());
        }
    }
}
