<?php

namespace App\Http\Controllers;

use App\Guard\AuthGuard;
use App\Models\Client;
use App\Models\ScheduleService;
use App\Service\OnlineScheduleService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OnlineScheduleController extends Controller
{
    private OnlineScheduleService $onlineScheduleService;

    public function __construct(OnlineScheduleService $onlineScheduleService)
    {
        $this->onlineScheduleService = $onlineScheduleService;
    }


    public function generateQr(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());
        $user->load(['employee', 'client']);
        $actingAsFamily = $request->boolean('as_family') && $user->client;

        if ($actingAsFamily) {
            $this->authorizeClientSchedule($user->client, (int) $request->input('schedule_services_id'));
        } elseif ($user->employee) {
            $request->merge([
                'employee_id' => $user->employee->employee_id,
            ]);
        } elseif ($user->client) {
            $this->authorizeClientSchedule($user->client,    (int) $request->input('schedule_services_id'));
        } else {
            throw new Exception('Employee or client record not found.', 403);
        }

        return response()->json([
            'token' => $this->onlineScheduleService->generateQr($request->all()),
            'expires_in' => OnlineScheduleService::QR_TTL_MINUTES * 60,
        ]);
    }

    private function authorizeClientSchedule(Client $client, int $scheduleServicesId): void
    {
        $scheduleService = ScheduleService::with('schedule')->find($scheduleServicesId);

        if (!$scheduleService || !$scheduleService->schedule) {
            throw new Exception('Schedule not found.', 404);
        }

        $hasAccess = $client->patientAccess()
            ->where('patient_id', $scheduleService->schedule->patient_id)
            ->where('have_access', true)
            ->exists();

        if (!$hasAccess) {
            throw new Exception('You do not have access to this schedule.', 403);
        }
    }

    public function verifyQr(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());
        $user->load('employee');

        if (!$user->employee) {
            throw new Exception('Only staff can scan attendance QR codes.', 403);
        }

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'type' => ['required', 'string'],
        ]);

        $validated['employee_id'] = $user->employee->employee_id;

        return $this->onlineScheduleService->verifyQr($validated);
    }
}
