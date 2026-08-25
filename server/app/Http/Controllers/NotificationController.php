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

    public function markRead(Request $request)
    {
        return $this->notificationService->markRead($request->all(), $request->user());
    }
}
