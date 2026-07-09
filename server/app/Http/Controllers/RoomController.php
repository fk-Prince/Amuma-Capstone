<?php

namespace App\Http\Controllers;

use App\Service\RoomService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoomController extends Controller
{
    private RoomService $roomService;

    public function __construct(RoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    public function index(Request $request)
    {
        return $this->roomService->listRoom($request->user(), $request->all());
    }

    public function store(Request $request)
    {
        return $this->roomService->createRoom($request->user(), $request->all());
    }
}
