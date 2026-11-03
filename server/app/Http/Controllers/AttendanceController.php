<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Models\Client;
use App\Models\ScheduleService;
use App\Service\AttendanceService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{

    public function __construct(private AttendanceService $attendanceService) {}


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
            ...$this->attendanceService->generateQr($request->all()),
            'expires_in' => AttendanceService::QR_TTL_MINUTES * 60,
            ...$this->attendanceService->qrContext((int) $request->input('schedule_services_id')),
        ]);
    }

    public function demoVerifyQr(Request $request)
    {
        AuthGuard::requireUser($request->user());

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'type' => ['required', 'string'],
            'employee_id' => ['required', 'integer'],
        ]);

        return $this->attendanceService->verifyQr($validated);
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

        return $this->attendanceService->verifyQr($validated);
    }

    public function lookupPatientVisit(Request $request)
    {
        $user = $this->requireStaff($request);

        $validated = $request->validate([
            'patient_uuid' => ['required', 'uuid'],
        ]);

        $validated['employee_id'] = $user->employee->employee_id;

        return response()->json($this->attendanceService->lookupPatientVisit($validated));
    }

    public function confirmPatientVisit(Request $request)
    {
        $user = $this->requireStaff($request);

        $validated = $request->validate([
            'patient_uuid' => ['required', 'uuid'],
            'schedule_id' => ['required', 'integer'],
            'employee_ids' => ['array'],
            'employee_ids.*' => ['integer'],
        ]);

        $validated['employee_id'] = $user->employee->employee_id;

        return response()->json([
            'message' => 'Attendance confirmed.',
            'data' => $this->attendanceService->confirmPatientVisit($validated, $user),
        ]);
    }

    public function timeOutPatientVisit(Request $request)
    {
        $user = $this->requireStaff($request);

        $validated = $request->validate([
            'patient_uuid' => ['required', 'uuid'],
            'schedule_id' => ['required', 'integer'],
        ]);

        $validated['employee_id'] = $user->employee->employee_id;

        return response()->json([
            'message' => 'Visit timed out and completed.',
            'data' => $this->attendanceService->timeOutPatientVisit($validated, $user),
        ]);
    }

    public function updateVisitAttendance(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Schedules, PermissionAction::Update);

        $validated = $request->validate([
            'schedule_id' => ['required', 'integer'],
            'employee_ids' => ['array'],
            'employee_ids.*' => ['integer'],
        ]);

        $validated['branch_id'] = $branch->branch_id;

        return response()->json([
            'message' => 'Attendance updated.',
            'data' => $this->attendanceService->updateVisitAttendance($validated, $request->user()),
        ]);
    }

    private function requireStaff(Request $request)
    {
        $user = AuthGuard::requireUser($request->user());
        $user->load('employee');

        if (!$user->employee) {
            throw new Exception('Only staff can scan patient QR codes.', 403);
        }

        return $user;
    }
}
