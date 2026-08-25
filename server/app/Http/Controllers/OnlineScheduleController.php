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

        // A dual-role account (both an employee and a client record —
        // e.g. a branch owner who is also listed as a patient's family
        // contact) needs an explicit signal for which context it's
        // acting in. Without it, such an account would always fall into
        // the staff branch below even when using the family portal,
        // forcing the lookup to their own employee_id instead of the
        // patient's actually-assigned caregiver.
        $actingAsFamily = $request->boolean('as_family') && $user->client;

        if ($actingAsFamily) {
            // Family generating the QR for the caregiver assigned to a
            // patient they have access to: no employee_id is forced, so
            // the service resolves whichever caregiver is actively
            // assigned to that schedule service.
            $this->authorizeClientSchedule(
                $user->client,
                (int) $request->input('schedule_services_id')
            );
        } elseif ($user->employee) {
            // Staff generating their own attendance QR: constrain the
            // lookup to their own assignment so one caregiver can't
            // generate another caregiver's QR.
            $request->merge([
                'employee_id' => $user->employee->employee_id,
            ]);
        } elseif ($user->client) {
            $this->authorizeClientSchedule(
                $user->client,
                (int) $request->input('schedule_services_id')
            );
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
