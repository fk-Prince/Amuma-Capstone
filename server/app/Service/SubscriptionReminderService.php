<?php

namespace App\Service;

use App\Enums\RoleEnum;
use App\Events\NotificationEvent;
use App\Models\BranchSubscription;
use App\Models\Subscription;
use App\Repository\EmployeeRepository;
use App\Repository\NotificationRepository;
use App\Repository\SubscriptionRepository;

class SubscriptionReminderService
{
    public const MESSAGE_TYPE = 'Subscription Renewal';

    private const CARD_REMINDER_DAYS = 3;

    private const RECIPIENT_ROLES = [
        RoleEnum::AgencyOwner->value,
        RoleEnum::BranchManager->value,
    ];

    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private EmployeeRepository $employeeRepository,
        private NotificationRepository $notificationRepository,
    ) {}

    public function sendDailyReminders(): int
    {
        $sent = 0;

        $subscriptions = $this->subscriptionRepository
            ->findDueForRenewalReminder(Subscription::RENEWAL_WINDOW_DAYS);

        foreach ($subscriptions as $subscription) {
            $message = $this->messageFor($subscription);

            if ($message) {
                $sent += $this->notifyBranches($subscription, $message);
            }
        }

        return $sent;
    }

    public function notifyBranches(Subscription $subscription, string $message): int
    {
        $sent = 0;

        foreach ($subscription->branchLinks as $link) {
            if ($link->status !== BranchSubscription::STATUS_APPROVED || !$link->branch) {
                continue;
            }

            $recipients = $this->employeeRepository->getBranchStaffByRoles(
                self::RECIPIENT_ROLES,
                $link->branch_id
            );

            foreach ($recipients as $recipient) {
                if ($this->notificationRepository->sentToday(
                    $recipient['user_id'],
                    $link->branch_id,
                    self::MESSAGE_TYPE
                )) {
                    continue;
                }

                $text = "{$link->branch->name}: {$message}";

                $this->notificationRepository->create([
                    'branch_id' => $link->branch_id,
                    'to_user_id' => $recipient['user_id'],
                    'from_user_id' => $recipient['user_id'],
                    'message_type' => self::MESSAGE_TYPE,
                    'message' => $text,
                ]);

                NotificationEvent::safe(new NotificationEvent(
                    (string) $recipient['uuid'],
                    (string) $link->branch->uuid,
                    $text,
                    (string) $subscription->uuid,
                    self::MESSAGE_TYPE,
                    null
                ));

                $sent++;
            }
        }

        return $sent;
    }

    private function messageFor(Subscription $subscription): ?string
    {
        $plan = $subscription->effectivePlan()?->name ?? 'subscription';
        $days = $subscription->daysLeft();
        $date = $subscription->end_date->toFormattedDateString();
        $unit = $days === 1 ? 'day' : 'days';
        $card = $subscription->savedCard();

        if ($card && $card['charges_on'] && ($card['is_trial'] || $card['auto_renew'])) {
            if ($days < 1 || $days > self::CARD_REMINDER_DAYS) {
                return null;
            }

            $amount = '₱' . number_format($card['amount'], 2);

            return $card['is_trial']
                ? "Your free trial ends in {$days} {$unit} ({$date}). We'll charge {$amount} for your {$plan} plan to your card ending in {$card['last4']} then."
                : "Your {$plan} subscription renews automatically in {$days} {$unit} ({$date}). We'll charge {$amount} to your card ending in {$card['last4']}.";
        }

        if ($subscription->awaitingTrialPayment()) {
            if ($days < 0) {
                return "Your free trial ended on {$date}. Pay for your {$plan} plan to unlock your branch.";
            }

            if ($days === 0) {
                return "Your free trial ends today ({$date}). Pay for your {$plan} plan to keep your branch running.";
            }

            return "Your free trial ends in {$days} {$unit} ({$date}). Pay for your {$plan} plan before then to keep your branch running.";
        }

        if ($days < 0) {
            return "Your {$plan} subscription expired on {$date}. Renew it now to keep your branch running.";
        }

        if ($days === 0) {
            return "Your {$plan} subscription ends today ({$date}). Renew it now to avoid interruption.";
        }

        return "Your {$plan} subscription ends in {$days} {$unit} ({$date}). Renew it before then to avoid interruption.";
    }
}
