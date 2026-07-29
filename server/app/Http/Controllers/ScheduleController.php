<?php

namespace App\Http\Controllers;

use App\Service\ScheduleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ScheduleController extends Controller
{
    private ScheduleService $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    public function store(Request $request)
    {
        return $this->scheduleService->createSchedule($request->user(), $request->all());
    }

    public function index(Request $request)
    {
        return $this->scheduleService->retrieveSchedule($request->user(), $request->all());
    }

    public function assign(Request $request)
    {
        return $this->scheduleService->assign($request->user(), $request->all());
    }

    public function update(Request $request, string $id)
    {

        return $this->scheduleService->checkConflictSchedule($request->user(), $request->all());
    }
}
