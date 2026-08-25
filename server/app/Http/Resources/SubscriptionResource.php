<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'billing_interval' => $this->billing_interval,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'created_at' => $this->created_at,

            'branch' => [
                'branch_id' => $this->branch?->branch_id,
                'uuid' => $this->branch?->uuid,
                'name' => $this->branch?->name,
                'email' => $this->branch?->email,
                'status' => $this->branch?->status,
                'is_verified' => $this->branch?->is_verified,
                'address' => $this->branch?->location?->full_address,
                'document' => $this->branch?->document,

                'agency' => [
                    'agency_id' => $this->branch?->agencies?->agency_id,
                    'uuid' => $this->branch?->agencies?->uuid,
                    'name' => $this->branch?->agencies?->name,
                    'email' => $this->branch?->agencies?->email,
                    'is_verified' => $this->branch?->agencies?->is_verified,
                    'address' => $this->branch?->agencies?->locations?->full_address,
                    'id_front' => $this->branch?->agencies?->id_front,
                    'id_back' => $this->branch?->agencies?->id_back,
                    'document' => $this->branch?->agencies?->document,
                ],
            ],

            'plan' => [
                'plan_id' => $this->plans?->plan_id,
                'name' => $this->plans?->name,
                'plan_code' => $this->plans?->plan_code,
            ],

            'payments' => $this->whenLoaded(
                'payments',
                fn() =>
                $this->payments->map(fn($payment) => [
                    'subscription_payment_id' => $payment->subscription_payment_id,
                    'payment_reference_id' => $payment->payment_reference_id,
                    'masked_card_number' => $payment->masked_card_number,
                    'price' => (float) $payment->price,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at?->toIso8601String(),
                ])
            ),
        ];
    }
}
