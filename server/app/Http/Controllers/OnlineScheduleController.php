<?php

namespace App\Http\Controllers;

use App\Guard\AuthGuard;
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
        $user->load('employee');
        if (!$user->employee) {
            throw new Exception('Employee record not found.',  403);
        }
        $request->merge([
            'employee_id' => $user->employee->employee_id,
        ]);

        return $this->onlineScheduleService->generateQr($request->all());
    }

    public function verifyQr(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'type' => ['required', 'string'],
        ]);

        return $this->onlineScheduleService->verifyQr($validated);
    }
}
