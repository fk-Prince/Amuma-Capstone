<?php

namespace App\Repository;

use App\Models\Plan;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PlanRepository
{

    public function getPlans()
    {
        return Plan::orderBy('plan_code')->get();
    }

    public function findByField(string $field, string $value)
    {
        return Plan::where($field, $value)->first();
    }

    public function update(Plan $plan, array $payload): Plan
    {
        $plan->update($payload);

        return $plan;
    }
}
