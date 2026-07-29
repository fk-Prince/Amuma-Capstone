<?php

namespace App\Http\Controllers;

use App\Http\Requests\Room\StoreRoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use App\Http\Requests\RoomRequest;
use App\Service\RoomService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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

    public function store(StoreRoomRequest $request)
    {
        return $this->roomService->createRoom($request->user(), $request->all());
    }

    public function update(UpdateRoomRequest $request, string $id)
    {
        return $this->roomService->updateRoom($request->user(), $id, $request->all());
    }

    public function overview(Request $request)
    {
        return $this->roomService->overview($request->user(), $request->all());
    }
}
