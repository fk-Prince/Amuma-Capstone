<?php

namespace App\Hooks;

use App\Models\Branch;
use App\Models\User;
use App\Service\BookingService;
use Illuminate\Support\Facades\Cache;

class FacilityWebhook
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

        $user = User::find($cached['user_id']);
        $branch = Branch::find($cached['branch_id']);
        $data = [...$cached['payload'], 'branch' => $branch];

        $result = [
            'xendit_invoice_id' => $payload['id'] ?? null,
            'masked_card_number' => null,
            'total' => $data['total'],
        ];

        return $this->bookingService->storePaidBooking($user, $data, $result, $data['breakdown']);
    }
}
