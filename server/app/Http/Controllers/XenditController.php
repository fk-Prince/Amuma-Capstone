<?php

namespace App\Http\Controllers;

use App\Factories\PaymentWebhook;
use App\Service\External\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
            return response()->json($this->settleIfPaid($reference) ?? ['status' => 'pending']);
        }

        return response()->json(['status' => 'unknown']);
    }

    private function settleIfPaid(string $reference): ?array
    {
        if (!Cache::add("xendit_verify_{$reference}", true, now()->addSeconds(2))) {
            return null;
        }

        $response = Http::withOptions(['verify' => false])
            ->withBasicAuth(config('services.xendit.secret_key'), '')
            ->get('https://api.xendit.co/v2/invoices', ['external_id' => $reference]);

        $invoice = $response->successful() ? ($response->json()[0] ?? null) : null;

        if (!$invoice || !in_array($invoice['status'] ?? null, ['PAID', 'SETTLED'], true)) {
            return null;
        }

        $payload = [
            'id' => $invoice['id'],
            'external_id' => $reference,
            'status' => 'PAID',
            'metadata' => $invoice['metadata'] ?? [],
        ];

        PaymentWebhook::makePayment($payload)->handle($payload);

        return Cache::get("xendit_payment_status_{$reference}");
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
