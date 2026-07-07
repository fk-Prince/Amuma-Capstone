<?php

namespace App\Http\Controllers;

use App\Service\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        return $this->notificationService->listNotification($request->all(), $request->user());
    }

    // public function store(Request $request)
    // {
    //     return $this->notificationService->createNotification($request->user(), $request->all());
    // }

    // public function show(Request $request, string $uuid)
    // {
    //     return $this->notificationService->getNotification($request->user(), $uuid);
    // }

    // public function update(Request $request, string $uuid)
    // {
    //     return $this->notificationService->updateNotification($request->user(), $uuid, $request->all());
    // }

    // public function destroy(Request $request, string $uuid)
    // {
    //     $this->notificationService->deleteNotification($request->user(), $uuid);
    //     return response()->json(['message' => 'Deleted successfully'], 200);
    // }

    // public function restore(Request $request, string $uuid)
    // {
    //     return $this->notificationService->restoreNotification($request->user(), $uuid);
    // }
}
