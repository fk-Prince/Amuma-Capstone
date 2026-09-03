<?php

namespace App\Http\Resources;

use App\Models\BranchSubscription;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subscription = $this->subscription;

        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'created_at' => $this->created_at,

            'billing_interval' => $subscription?->billing_interval,
            'start_date' => $subscription?->start_date,
            'end_date' => $subscription?->end_date,

            'subscription' => [
                'uuid' => $subscription?->uuid,
                'status' => $subscription?->status,
                'start_date' => $subscription?->start_date,
                'end_date' => $subscription?->end_date,
                'branch_limit' => Subscription::BRANCH_LIMIT,

                'covered_branches' => $subscription
                    ? $subscription->branchLinks()
                    ->where('status', '!=', BranchSubscription::STATUS_REJECTED)
                    ->with('branch')
                    ->get()
                    ->map(fn($link) => [
                        'uuid' => $link->branch?->uuid,
                        'name' => $link->branch?->name,
                        'is_verified' => $link->branch?->is_verified,
                        'status' => $link->status,
                    ])
                    ->values()
                    : [],
            ],

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
                'plan_id' => $subscription?->plans?->plan_id,
                'name' => $subscription?->plans?->name,
                'plan_code' => $subscription?->plans?->plan_code,
            ],

            // An upgrade already paid for that starts when the current period
            // runs out; null once it has taken over.
            'pending_plan' => $subscription?->pending_plan_id ? [
                'name' => $subscription->pendingPlan?->name,
                'plan_code' => $subscription->pendingPlan?->plan_code,
                'starts_at' => $subscription->pending_plan_starts_at?->toDateString(),
                'is_due' => $subscription->pendingPlanIsDue(),
            ] : null,

            'payments' => $subscription?->relationLoaded('payments')
                ? $subscription->payments->map(fn($payment) => [
                    'subscription_payment_id' => $payment->subscription_payment_id,
                    // Which plan this payment bought — the subscription's own
                    // plan can have changed since, so it cannot be inferred.
                    'plan_name' => $payment->plan?->name,
                    'payment_reference_id' => $payment->payment_reference_id,
                    'masked_card_number' => $payment->masked_card_number,
                    'price' => (float) $payment->price,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at?->toIso8601String(),
                ])->values()
                : [],
        ];
    }
}
