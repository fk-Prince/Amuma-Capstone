<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Service\Booking\BookingHelper;
use App\Service\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{

    public function __construct(
        private BookingService $bookingService,
        private BookingHelper $bookingHelper
    ) {
        $this->bookingService = $bookingService;
    }

    public function index(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Bookings, PermissionAction::Read);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->bookingService->listBooking($request->user(), $request->all());
    }

    public function store(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        $request->merge([
            'branch' => $branch,
            'branch_id' => $branch->branch_id
        ]);

        if ($request->action === 'regular') {
            return $this->bookingService->createBooking($request->user(), $request->all());
        } else if ($request->action === 'complete-admission') {
            return $this->bookingService->completePayment($request->user(), $request->all());
        }
    }

    public function action(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        $request->merge([
            'branch' => $branch,
            'branch_id' => $branch->branch_id
        ]);

        if ($request->action === 'total') {
            return $this->bookingHelper->getTotal($request->all());
        } else if ($request->action === 'approve') {
            AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Bookings, PermissionAction::Approve);
            $request->merge([
                'user' => $request->user(),
            ]);
            return $this->bookingService->bookingAction($request->all());
        } else if ($request->action === 'reject') {
            AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Bookings, PermissionAction::Approve);
            return $this->bookingService->reject($request->all());
        }
    }

    public function show(Request $request, string $id)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Bookings, PermissionAction::Read);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return $this->bookingService->show($request->all());
    }

    public function overview(Request $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Bookings, PermissionAction::Read);
        $request->merge([
            'branch_id' => $branch->branch_id,
        ]);
        return  $this->bookingService->overview($request->all());
    }
}
