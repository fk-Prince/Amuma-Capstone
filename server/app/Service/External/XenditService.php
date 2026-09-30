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

    public static function invoice(?string $invoiceId): ?array
    {
        if (!$invoiceId) {
            return null;
        }

        $response = Http::withOptions(['verify' => false])
            ->withBasicAuth(config('services.xendit.secret_key'), '')
            ->get("https://api.xendit.co/v2/invoices/{$invoiceId}");

        return $response->successful() ? $response->json() : null;
    }

    public static function cardCharge(?string $chargeId): ?array
    {
        if (!$chargeId) {
            return null;
        }

        $response = Http::withOptions(['verify' => false])
            ->withBasicAuth(config('services.xendit.secret_key'), '')
            ->get("https://api.xendit.co/credit_card_charges/{$chargeId}");

        return $response->successful() ? $response->json() : null;
    }

    public static function refundXenditPayment(string $id, float $amount, bool $isCardCharge = false): bool
    {
        try {
            $request = Http::withOptions([
                'verify' => false,
            ])->withBasicAuth(config('services.xendit.secret_key'), '');

            $response = $isCardCharge
                ? $request->post(
                    "https://api.xendit.co/credit_card_charges/{$id}/refunds",
                    [
                        'external_id' => (string) Str::uuid(),
                        'amount' => $amount,
                    ]
                )
                : $request->post('https://api.xendit.co/refunds', [
                    'invoice_id' => $id,
                    'reference_id' => (string) Str::uuid(),
                    'amount' => $amount,
                    'reason' => 'CANCELLATION',
                ]);

            if (in_array($response->json('error_code'), [
                'REFUND_AMOUNT_EXCEEDED_ERROR',
                'MAXIMUM_REFUND_AMOUNT_REACHED_ERROR',
            ], true)) {
                Log::warning('Xendit payment was already refunded', [
                    'id' => $id,
                    'amount' => $amount,
                ]);

                return true;
            }

            $response->throw();

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
