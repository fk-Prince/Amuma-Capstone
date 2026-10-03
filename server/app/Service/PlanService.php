<?php

namespace App\Service;

use App\Models\Plan;
use App\Repository\PlanRepository;


class PlanService
{
    private PlanRepository $planRepository;

    public function __construct(PlanRepository $planRepository)
    {
        $this->planRepository = $planRepository;
    }

    public function getPlans()
    {
        return $this->planRepository->getPlans();
    }

    public function updatePlan(Plan $plan, array $payload)
    {
        $updated = $this->planRepository->update($plan, [
            'description' => $payload['description'] ?? $plan->description,
            'price' => $payload['price'] ?? $plan->price,
            'additional_branch_price' => $payload['additional_branch_price'] ?? $plan->additional_branch_price,
        ]);

        return response()->json([
            'status' => true,
            'message' => __('Plan updated successfully.'),
            'plan' => $updated,
        ]);
    }
}
