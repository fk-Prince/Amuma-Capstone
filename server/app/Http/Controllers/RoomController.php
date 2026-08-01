<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Requests\Room\StoreRoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use App\Http\Requests\RoomRequest;
use App\Service\RoomService;
use Exception;
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
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::RoomsAndBeds, PermissionAction::Read);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->roomService->listRoom($request->user(), $request->all());
    }

    public function store(StoreRoomRequest $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid, true);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::RoomsAndBeds, PermissionAction::Create);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);

        return $this->roomService->createRoom($request->user(), $request->all());
    }

    public function update(UpdateRoomRequest $request, string $id)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid, true);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::RoomsAndBeds, PermissionAction::Update);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->roomService->updateRoom($request->user(), $id, $request->all());
    }

    public function overview(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::RoomsAndBeds, PermissionAction::Read);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->roomService->overview($request->user(), $request->all());
    }
}
