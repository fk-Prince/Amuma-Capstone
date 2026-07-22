<?php

namespace App\Http\Controllers;

use App\Service\ScheduleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    private ScheduleService $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    public function index(Request $request)
    {
        return $this->scheduleService->listSchedule(
            $request->user(), 
            $request->input('per_page', 15)
        );
    }

    public function store(Request $request)
    {
        return $this->scheduleService->createSchedule($request->user(), $request->all());
    }

    public function show(Request $request, string $uuid)
    {
        return $this->scheduleService->getSchedule($request->user(), $uuid);
    }

    public function update(Request $request, string $uuid)
    {
        return $this->scheduleService->updateSchedule($request->user(), $uuid, $request->all());
    }

    public function destroy(Request $request, string $uuid)
    {
        $this->scheduleService->deleteSchedule($request->user(), $uuid);
        return response()->json(['message' => 'Deleted successfully'], 200);
    }
    
    public function restore(Request $request, string $uuid)
    {
        return $this->scheduleService->restoreSchedule($request->user(), $uuid);
    }
}