<?php

namespace App\Http\Requests\Subscription;

class BranchResubmitPurchaseRequest extends BranchResubmitRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'subscription_uuid' => ['required', 'string', 'exists:subscriptions,uuid'],
            'payment_method' => ['required', 'in:GCASH,CREDIT-CARD'],
            'token_id' => ['nullable', 'string'],
            'authentication_id' => ['nullable', 'string'],
        ];
    }
}
