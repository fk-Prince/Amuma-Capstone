<?php

namespace App\Hooks;

use App\Models\User;
use App\Service\BookingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FacilityWebhook
{

    public function __construct(private BookingService $bookingService) {}

    public function handle(array $payload)
    {
        $reference = $payload['external_id'] ?? null;

        if (!$reference) {
            return response()->json([
                'message' => 'Missing reference'
            ], 400);
        }

        $cachedPayload = Cache::get("xendit_payment_{$reference}");

        if (!$cachedPayload) {
            return response()->json([
                'message' => 'Payment data expired'
            ], 404);
        }

        $payload['metadata'] = $cachedPayload;
        $payload['xendit_invoice_id'] = $payload['id'];
        $user = $cachedPayload['user'] ?? null;

        // return $this->bookingService->createPaymentBooking($user, $payload);
        return null;
    }
}
