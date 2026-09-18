<?php

namespace App\Service\External;


use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class XenditService
{
    public static function getMetadata(array $payload): ?array
    {
        $status = $payload['status'] ?? null;
        $externalId = $payload['external_id'] ?? null;

        if (!$externalId || !in_array($status, ['PAID', 'CAPTURED'], true)) {
            return null;
        }

        $response = Http::withOptions(['verify' => false])
            ->withBasicAuth(config('services.xendit.secret_key'), '')
            ->get('https://api.xendit.co/v2/invoices', [
                'external_id' => $externalId,
            ]);

        if (!$response->successful()) {
            Log::error('Xendit invoice lookup failed', [
                'external_id' => $externalId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $invoices = $response->json();
        $invoice = $invoices[0] ?? null;

        if ($invoice && !in_array($invoice['status'] ?? null, ['PAID', 'SETTLED'], true)) {
            return null;
        }

        if (!$invoice || empty($invoice['metadata']['payment_type'] ?? $invoice['metadata']['type'] ?? null)) {
            Log::error('Xendit invoice found but missing metadata', [
                'external_id' => $externalId,
                'invoice_id' => $invoice['id'] ?? null,
            ]);

            return null;
        }

        return $invoice['metadata'];
    }

    public static function refundXenditPayment(string $id, float $amount, bool $isCardCharge = false): bool
    {
        try {
            $request = Http::withOptions([
                'verify' => false,
            ])->withBasicAuth(config('services.xendit.secret_key'), '');

            if ($isCardCharge) {
                $request->post(
                    "https://api.xendit.co/credit_card_charges/{$id}/refunds",
                    [
                        'external_id' => (string) Str::uuid(),
                        'amount' => $amount,
                    ]
                )->throw();
            } else {
                $request->post('https://api.xendit.co/refunds', [
                    'invoice_id' => $id,
                    'reference_id' => (string) Str::uuid(),
                    'amount' => $amount,
                    'reason' => 'CANCELLATION',
                ])->throw();
            }

            return true;
        } catch (Exception $e) {
            Log::error('Xendit refund failed', [
                'id' => $id,
                'amount' => $amount,
                'is_card_charge' => $isCardCharge,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
