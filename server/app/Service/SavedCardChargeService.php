<?php

namespace App\Service;

use App\Models\Subscription;
use App\Models\User;
use App\Repository\SubscriptionRepository;
use App\Service\External\XenditService;
use App\Service\Payment\CardPayment;
use Carbon\Carbon;

class SavedCardChargeService
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private SubscriptionRepository $subscriptionRepository,
        private SubscriptionReminderService $reminderService,
        private CardPayment $cardPayment,
    ) {}

    public function chargeDue(): int
    {
        $charged = 0;

        foreach ($this->subscriptionRepository->findDueForCardCharge() as $subscription) {
            $charged += (int) $this->charge($subscription);
        }

        return $charged;
    }

    private function charge(Subscription $subscription): bool
    {
        $isTrial = $subscription->awaitingTrialPayment();
        $plan = $subscription->plans?->name ?? 'subscription';
        $last4 = $subscription->cardLast4();
        $card = [
            'card_token_id' => $subscription->card_token_id,
            'masked_card_number' => $subscription->masked_card_number,
            'card_brand' => $subscription->card_brand,
            'auto_renew' => true,
        ];
        $keepCard = $subscription->auto_renew;

        if (!$this->subscriptionRepository->releaseCard($subscription->subscription_id)) {
            return false;
        }

        $owner = User::find($subscription->agency?->registered_by);
        $detail = null;
        $result = null;

        try {
            $detail = $this->subscriptionService->savedCardChargeDetail($subscription, $owner);
            $result = $this->cardPayment->chargeSavedCard($card['card_token_id'], $detail, $owner?->email);
        } catch (\Throwable $e) {
            report($e);
        }

        if (!$result) {
            $this->reminderService->notifyBranches($subscription, $isTrial
                ? "We couldn't charge your card ending in {$last4} when your free trial ended. Pay for your plan in Manage Subscription to unlock your branch."
                : "We couldn't charge your card ending in {$last4} to renew your {$plan} subscription, so auto-renew is now off. Renew it in Manage Subscription to keep your branch running.");

            return false;
        }

        try {
            $this->subscriptionService->settlePayment([...$result, 'card' => $keepCard ? $card : null]);
        } catch (\Throwable $e) {
            report($e);

            XenditService::refundXenditPayment($result['xendit_invoice_id'], (float) $detail['total_amount'], true);

            $this->reminderService->notifyBranches(
                $subscription,
                "Your card ending in {$last4} was charged but your subscription couldn't be updated, so the payment is being refunded. Pay for your plan in Manage Subscription."
            );

            return false;
        }

        $amount = '₱' . number_format((float) $detail['total_amount'], 2);
        $until = Carbon::parse($detail['endDate'])->toFormattedDateString();

        $this->reminderService->notifyBranches($subscription, $isTrial
            ? "Your free trial ended and {$amount} was charged to your card ending in {$last4}. Your paid year of {$plan} starts today."
            : "Your {$plan} subscription renewed automatically. {$amount} was charged to your card ending in {$last4}, and it now runs until {$until}.");

        return true;
    }
}
