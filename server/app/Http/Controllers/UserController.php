<?php

namespace App\Http\Controllers;

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
        return $this->userService->fetchMe($request->user());
    }

    public function index(Request $request)
    {
        return $this->userService->getUserBranch($request->user());
    }
}
