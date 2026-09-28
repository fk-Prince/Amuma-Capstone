<?php

namespace App\Http\Controllers;

use App\Service\PatientAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PatientAccessController extends Controller
{

    public function __construct(private PatientAccessService $patientAccessService) {}

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

        if ($request->action === 'invoices') {
            $request->validate([
                'patient_id' => ['required', 'integer'],
                'page' => ['nullable', 'integer', 'min:1'],
                'per_page' => ['nullable', 'integer', 'min:1', 'max:20'],
            ]);

            return $this->patientAccessService->invoices($payload);
        }

        if ($request->action === 'invoice') {
            $request->validate([
                'patient_id' => ['required', 'integer'],
                'invoice_code' => ['required', 'string', 'max:50'],
            ]);

            return $this->patientAccessService->invoice($payload);
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

        if ($request->action === 'cancel_booking') {
            return $this->patientAccessService->cancelBooking($payload, $request->user());
        }

        if ($request->action === 'cancel_admission') {
            return $this->patientAccessService->cancelAdmission($payload, $request->user());
        }

        if ($request->action === 'request_schedule_review') {
            return $this->patientAccessService->requestScheduleReview($payload, $request->user());
        }
    }
}
