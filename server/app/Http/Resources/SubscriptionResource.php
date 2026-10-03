<?php

namespace App\Http\Resources;

use App\Models\BranchSubscription;
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

        $isRejected = $this->status === BranchSubscription::STATUS_REJECTED;

        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'is_first_branch' => $isFirstBranch,
            'rejection_reason' => $isRejected ? $this->latestRejection?->reason : null,
            'rejected_at' => $isRejected ? $this->latestRejection?->created_at : null,
            'rejection_logs_count' => (int) ($this->branch?->rejection_logs_count ?? 0),

            'mode' => $subscription?->mode,
            'start_date' => $subscription?->start_date,
            'end_date' => $subscription?->end_date,

            'renewal' => $subscription?->renewalSummary(),

            'subscription' => [
                'uuid' => $subscription?->uuid,
                'status' => $subscription?->status,
                'start_date' => $subscription?->start_date,
                'end_date' => $subscription?->end_date,
                'branch_limit' => $subscription?->branchLimit(),
                'additional_branches' => $subscription?->additionalBranchCount(),
                'additional_branch_price' => (float) $subscription?->plans?->additional_branch_price,

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
                        'contact_number' => $link->branch?->contact_number,
                        'address' => $link->branch?->location?->full_address,
                        'latitude' => $link->branch?->location?->latitude,
                        'longitude' => $link->branch?->location?->longitude,
                        'document' => $link->branch?->document,
                        'image' => $link->branch?->image,
                        'tin' => data_get($link->branch?->settings, 'tin'),
                        'branch_status' => $link->branch?->status,
                        'status' => $link->status,
                        'type' => $link->type,
                    ])
                    ->values()
                    : [],
            ],

            'branch' => [
                'branch_id' => $this->branch?->branch_id,
                'uuid' => $this->branch?->uuid,
                'name' => $this->branch?->name,
                'email' => $this->branch?->email,
                'contact_number' => $this->branch?->contact_number,
                'status' => $this->branch?->status,
                'address' => $this->branch?->location?->full_address,
                'latitude' => $this->branch?->location?->latitude,
                'longitude' => $this->branch?->location?->longitude,
                'document' => $this->branch?->document,
                'image' => $this->branch?->image,
                'tin' => data_get($this->branch?->settings, 'tin'),

                'agency' => [
                    'agency_id' => $this->branch?->agencies?->agency_id,
                    'uuid' => $this->branch?->agencies?->uuid,
                    'name' => $this->branch?->agencies?->name,
                    'email' => $this->branch?->agencies?->email,
                    'status' => $this->branch?->agencies?->status,
                    'address' => $this->branch?->agencies?->locations?->full_address,
                    'latitude' => $this->branch?->agencies?->locations?->latitude,
                    'longitude' => $this->branch?->agencies?->locations?->longitude,
                    'image' => $this->branch?->agencies?->image,
                    'id_front' => $this->branch?->agencies?->id_front,
                    'id_back' => $this->branch?->agencies?->id_back,
                    'document' => $this->branch?->agencies?->document,
                    'registered_by' => MaskUtil::email(
                        $this->branch?->agencies?->registrant?->email
                    ),
                ],
            ],

            'plan' => $subscription?->planSummary(),

            'pending_plan' => $subscription?->pendingPlanSummary(),

            'payments' => $subscription?->relationLoaded('payments')
                ? $subscription->payments->map(fn($payment) => $payment->historyRow())->values()
                : [],
        ];
    }
}
