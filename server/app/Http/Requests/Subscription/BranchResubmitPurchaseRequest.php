<?php

namespace App\Http\Requests\Subscription;

use App\Models\Plan;
use Illuminate\Validation\Rule;

class BranchResubmitPurchaseRequest extends BranchResubmitRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'plan_code' => ['required', 'string', 'exists:plans,plan_code'],
            'plan_type' => ['required', Rule::in(Plan::TYPES)],
            'payment_method' => ['required', 'in:GCASH,CREDIT-CARD'],
            'token_id' => ['nullable', 'string'],
            'authentication_id' => ['nullable', 'string'],
        ];
    }
}
