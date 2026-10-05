<?php

namespace App\Http\Controllers;

use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Service\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{

    public function __construct(private UserService $userService) {}

    public function fetchMe(Request $request)
    {
        if (!$request->user()) {
            return [];
        }
        return $this->userService->fetchMe($request->user());
    }

    public function getUserBranch(Request $request)
    {
        return $this->userService->getUserBranch($request->user());
    }

    public function completeOnboarding(Request $request, string $area)
    {
        return $this->userService->completeOnboarding($request->user(), $area);
    }

    public function profile(Request $request)
    {
        return $this->userService->profile($request->user());
    }

    public function transactions(Request $request)
    {
        return $this->userService->transactions($request->user());
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar');
        }

        return $this->userService->updateProfile($request->user(), $data);
    }
}
