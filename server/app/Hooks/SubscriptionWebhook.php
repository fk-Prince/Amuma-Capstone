<?php

namespace App\Hooks;

use App\Service\SubscriptionService;
use Illuminate\Support\Facades\Cache;

class SubscriptionWebhook
{
    public function __construct(private SubscriptionService $subscriptionService) {}

    public function handle(array $payload)
    {
        $reference = $payload['external_id'] ?? null;
        $metadata = $reference ? Cache::pull("xendit_payment_{$reference}") : null;

        if (!$metadata) {
            return response()->json([
                'message' => 'Payment already processed or expired.',
                'status' => 'ignored',
            ], 200);
        }

        $result = [
            'metadata' => $metadata,
            'external_id' => $reference,
            'xendit_invoice_id' => $payload['id'] ?? null,
            'masked_card_number' => null,
        ];

        $response = $this->subscriptionService->settlePayment($result);

        Cache::put(
            "xendit_payment_status_{$reference}",
            [
                'status' => $response->getStatusCode() < 300 ? 'submitted' : 'failed',
                'message' => $response->getData(true)['message'] ?? null,
            ],
            now()->addDay()
        );

        return $response;
    }
}
