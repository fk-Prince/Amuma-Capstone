<?php

namespace App\Hooks;

use App\Models\User;
use App\Service\PaymentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class PortalBalanceWebhook
{
    public function __construct(private PaymentService $paymentService) {}

    public function handle(array $payload)
    {
        $reference = $payload['external_id'] ?? null;
        $cached = $reference ? Cache::pull("xendit_payment_{$reference}") : null;

        if (!$cached) {
            return response()->json([
                'message' => 'Payment already processed or expired.',
                'status' => 'ignored',
            ], 200);
        }

        $client = User::find($cached['user_id'])?->client;

        try {
            if (!$client) {
                throw new \Exception('The paying account could not be found.', 404);
            }

            $result = $this->paymentService->payBalance($client, [
                ...$cached['payload'],
                'paid_via' => 'GCASH',
                'payment_reference' => $payload['id'] ?? $reference,
            ]);

            $status = ['status' => 'submitted'];
        } catch (Throwable $e) {
            report($e);

            Log::warning('Portal GCash balance payment could not be applied', [
                'reference' => $reference,
                'message' => $e->getMessage(),
            ]);

            $result = ['message' => $e->getMessage()];
            $status = ['status' => 'failed', 'message' => $e->getMessage()];
        }

        Cache::put("xendit_payment_status_{$reference}", $status, now()->addDay());

        return response()->json(
            $status['status'] === 'submitted' ? ['status' => 'submitted'] : $result,
            $status['status'] === 'submitted' ? 200 : 422
        );
    }
}
