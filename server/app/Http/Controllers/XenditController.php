<?php

namespace App\Http\Controllers;

use App\Factories\PaymentWebhook;
use App\Service\External\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class XenditController extends Controller
{

    public function checkStatus(string $reference)
    {
        $result = Cache::get("xendit_payment_status_{$reference}");

        if ($result) {
            return response()->json($result);
        }

        if (Cache::has("xendit_payment_{$reference}")) {
            return response()->json(['status' => 'pending']);
        }

        return response()->json(['status' => 'unknown']);
    }

    public function xenditWebhook(Request $request)
    {
        $payload = $request->all();
        $metadata = XenditService::getMetadata($payload);
        if (!$metadata) {
            return response()->json([
                'message' => 'Payment unavailable, Try again later.',
                'status' => 'ignored'
            ], 200);
        }
        $payload['metadata'] = $metadata;

        $handler = PaymentWebhook::makePayment($payload);

        return $handler->handle($payload);
    }
}
