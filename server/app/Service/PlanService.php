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
            'monthly_price' => $payload['monthly_price'] ?? $plan->monthly_price,
            'yearly_price' => $payload['yearly_price'] ?? $plan->yearly_price,
        ]);

        return response()->json([
            'status' => true,
            'message' => __('Plan updated successfully.'),
            'plan' => $updated,
        ]);
    }
}
