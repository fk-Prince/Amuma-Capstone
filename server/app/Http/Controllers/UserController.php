<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\StoreEmployeeRequest;
use App\Http\Requests\Auth\UpdateEmployeeRequest;
use App\Service\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //

    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

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
}
