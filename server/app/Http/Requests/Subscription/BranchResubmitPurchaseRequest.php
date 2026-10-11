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
            'subscription_uuid' => ['nullable', 'string', 'exists:subscriptions,uuid'],
            'plan_code' => ['required_without:subscription_uuid', 'nullable', 'string', 'exists:plans,plan_code'],
            'plan_type' => ['required_without:subscription_uuid', 'nullable', Rule::in(Plan::TYPES)],
            'payment_method' => ['required', 'in:GCASH,CREDIT-CARD'],
            'token_id' => ['nullable', 'string'],
            'authentication_id' => ['nullable', 'string'],
        ];
    }
}
