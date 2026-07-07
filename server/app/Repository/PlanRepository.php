<?php

namespace App\Repository;

use App\Models\Plan;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PlanRepository
{

    public function getPlans()
    {
        return Plan::all();
    }

    public function findByField(string $field, string $value)
    {
        return Plan::where($field, $value)->first();
    }
}
