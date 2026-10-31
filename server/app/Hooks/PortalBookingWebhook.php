<?php

namespace App\Hooks;

use App\Models\User;
use App\Service\BookingService;
use App\Service\External\XenditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class PortalBookingWebhook
{
    public function __construct(private BookingService $bookingService) {}

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

        $invoiceId = $payload['id'] ?? $reference;

        try {
            $user = User::findOrFail($cached['user_id']);

            $this->bookingService->settlePortalPayment($user, $cached['payload']['reference_id'], [
                'payment_method' => 'GCASH',
                'xendit_invoice_id' => $invoiceId,
                'masked_card_number' => null,
            ]);

            $status = ['status' => 'submitted', 'reference_id' => $cached['payload']['reference_id']];
        } catch (Throwable $e) {
            report($e);

            Log::warning('Portal GCash booking payment could not be applied', [
                'reference' => $reference,
                'message' => $e->getMessage(),
            ]);

            XenditService::refundXenditPayment($invoiceId, (float) $cached['payload']['amount']);

            $status = ['status' => 'failed', 'message' => $e->getMessage()];
        }

        Cache::put("xendit_payment_status_{$reference}", $status, now()->addDay());

        return response()->json($status, $status['status'] === 'submitted' ? 200 : 422);
    }
}
