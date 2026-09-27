<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Events\NotificationEvent;
use App\Models\BranchSubscription;
use App\Models\Subscription;
use App\Repository\EmployeeRepository;
use App\Repository\NotificationRepository;
use App\Repository\SubscriptionRepository;

class SubscriptionReminderService
{
    public const MESSAGE_TYPE = 'Subscription Renewal';

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

            foreach ($subscription->branchLinks as $link) {
                if ($link->status !== BranchSubscription::STATUS_APPROVED || !$link->branch) {
                    continue;
                }

                $recipients = $this->employeeRepository->getBranchUsersWithPermission(
                    $link->branch_id,
                    ModuleEnum::BranchSettings,
                    PermissionAction::Read
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

                    event(new NotificationEvent(
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
        }

        return $sent;
    }

    private function messageFor(Subscription $subscription): string
    {
        $plan = $subscription->effectivePlan()?->name ?? 'subscription';
        $days = $subscription->daysLeft();
        $date = $subscription->end_date->toFormattedDateString();

        if ($days < 0) {
            return "Your {$plan} subscription expired on {$date}. Renew it now to keep your branch running.";
        }

        if ($days === 0) {
            return "Your {$plan} subscription ends today ({$date}). Renew it now to avoid interruption.";
        }

        $unit = $days === 1 ? 'day' : 'days';

        return "Your {$plan} subscription ends in {$days} {$unit} ({$date}). Renew it before then to avoid interruption.";
    }
}
