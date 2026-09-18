<?php

namespace App\Http\Resources;

use App\Models\BranchSubscription;
use App\Models\Subscription;
use App\Utils\MaskUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subscription = $this->subscription;

        $firstLinkId = $subscription
            ? $subscription->branchLinks()->min('branch_subscription_id')
            : null;

        $isFirstBranch = $firstLinkId !== null
            && (int) $this->branch_subscription_id === (int) $firstLinkId;

        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'is_first_branch' => $isFirstBranch,

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
                    ->orderBy('branch_subscription_id')
                    ->get()
                    ->map(fn($link) => [
                        'uuid' => $link->branch?->uuid,
                        'name' => $link->branch?->name,
                        'email' => $link->branch?->email,
                        'address' => $link->branch?->location?->full_address,
                        'document' => $link->branch?->document,
                        'tin' => data_get($link->branch?->settings, 'tin'),
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
                'tin' => data_get($this->branch?->settings, 'tin'),

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
                    'registered_by' => MaskUtil::email(
                        $this->branch?->agencies?->registrant?->email
                    ),
                ],
            ],

            'plan' => [
                'plan_id' => $subscription?->plans?->plan_id,
                'name' => $subscription?->plans?->name,
                'plan_code' => $subscription?->plans?->plan_code,
            ],

            'pending_plan' => $subscription?->pending_plan_id ? [
                'name' => $subscription->pendingPlan?->name,
                'plan_code' => $subscription->pendingPlan?->plan_code,
                'starts_at' => $subscription->pending_plan_starts_at?->toDateString(),
                'is_due' => $subscription->pendingPlanIsDue(),
            ] : null,

            'payments' => $subscription?->relationLoaded('payments')
                ? $subscription->payments->map(fn($payment) => [
                    'subscription_payment_id' => $payment->subscription_payment_id,
                    'plan_name' => $payment->plan?->name,
                    'xendit_invoice_id' => $payment->xendit_invoice_id,
                    'payment_reference_id' => $payment->payment_reference_id,
                    'masked_card_number' => $payment->masked_card_number,
                    'price' => (float) $payment->price,
                    'status' => $payment->status,
                    'type' => $payment->type,
                    'billing_interval' => $payment->billing_interval,
                    'payment_method' => $payment->payment_method,
                    'created_at' => $payment->created_at?->toIso8601String(),
                ])->values()
                : [],
        ];
    }
}
