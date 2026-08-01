<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Requests\Bed\StoreBedRequest;
use App\Http\Requests\Bed\UpdateBedRequest;
use App\Service\BedService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BedController extends Controller
{
    private BedService $bedService;

    public function __construct(BedService $bedService)
    {
        $this->bedService = $bedService;
    }

    public function store(StoreBedRequest $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid, true);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::RoomsAndBeds, PermissionAction::Create);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->bedService->createBed($request->user(), $request->all());
    }

    public function update(UpdateBedRequest $request, string $id)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid, true);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::RoomsAndBeds, PermissionAction::Update);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->bedService->updateBed($request->user(), $request->all(), $id);
    }
}
