<?php

namespace App\Http\Requests\Subscription;

class BranchResubmitPurchaseRequest extends BranchResubmitRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'plan_code' => ['required', 'string', 'exists:plans,plan_code'],
            'billing_interval' => ['required', 'string'],
            'payment_method' => ['required', 'in:GCASH,CREDIT-CARD'],
            'token_id' => ['nullable', 'string'],
            'authentication_id' => ['nullable', 'string'],
        ];
    }
}
