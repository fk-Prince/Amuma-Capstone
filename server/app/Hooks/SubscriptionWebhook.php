<?php

namespace App\Hooks;

use App\Models\User;
use App\Service\SubscriptionService;
use Illuminate\Support\Facades\Log;

class SubscriptionWebhook
{
    private SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function handle(array $payload)
    {
        return $this->subscriptionService->newSubscriber($payload);
    }
}
