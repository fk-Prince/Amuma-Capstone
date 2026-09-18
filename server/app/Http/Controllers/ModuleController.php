<?php

namespace App\Http\Controllers;

use App\Service\ModuleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ModuleController extends Controller
{

    public function __construct(private ModuleService $moduleService)
    {
        $this->moduleService = $moduleService;
    }

    public function index(Request $request)
    {
        return $this->moduleService->listModule($request->all(), $request->user());
    }
}
