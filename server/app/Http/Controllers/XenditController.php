<?php

namespace App\Http\Controllers;

use App\Factories\PaymentWebhook;
use App\Service\Utils\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XenditController extends Controller
{


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
