<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Service\PlanService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    private PlanService $planService;

    public function __construct(PlanService $planService)
    {
        $this->planService = $planService;
    }

    public function index(Request $request)
    {
        return $this->planService->getPlans();
    }

    public function update(Request $request, Plan $plan)
    {
        return $this->planService->updatePlan($plan, $request->all());
    }
}
