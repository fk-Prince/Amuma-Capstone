<?php

namespace App\Service\Payment;

use App\Interfaces\IFacilityPayment;
use App\Interfaces\ISubscriptionPayment;
use App\Models\Branch;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GCashPayment implements ISubscriptionPayment, IFacilityPayment
{
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.xendit.secret_key');
    }

    public function subscriptionInvoice(array $payload, array $subscription)
    {
        $user = Auth::user();
        $reference = (string) Str::uuid();
        $isRenewal = ($subscription['type'] ?? null) === 'renewal';

        Cache::put(
            "xendit_payment_{$reference}",
            json_decode(json_encode([
                ...$subscription,
                'payment_method' => $subscription['method'],
            ]), true),
            now()->addDay()
        );

        $response = Http::withOptions([
            'verify' => false
        ])->withBasicAuth($this->secretKey, '')
            ->post('https://api.xendit.co/v2/invoices', [
                'external_id' => $reference,
                'amount' => $subscription['total_amount'],
                'payer_email' => $user->email,
                'payment_methods' => ['GCASH'],
                'success_redirect_url' => $this->subscriptionRedirectUrl($reference, 'success'),
                'failure_redirect_url' => $this->subscriptionRedirectUrl($reference, 'failed'),
                'metadata' => [
                    'payment_type' => $isRenewal ? 'RENEWAL' : 'SUBSCRIPTION',
                    'reference_id' => $reference,
                ],
            ]);

        return $this->invoiceResponse($response, $reference);
    }

    public function facilityBilling(array $payload)
    {
        $user = Auth::user();
        $reference = (string) Str::uuid();

        Cache::put(
            "xendit_payment_{$reference}",
            [
                'user_id' => $user->user_id,
                'branch_id' => $payload['branch']->branch_id,
                'payload' => json_decode(json_encode(
                    Arr::except($payload, ['branch', 'user', 'token_id', 'authentication_id'])
                ), true),
            ],
            now()->addDay()
        );

        $response = Http::withOptions([
            'verify' => false
        ])->withBasicAuth($this->secretKey, '')
            ->post('https://api.xendit.co/v2/invoices', [
                'external_id' => $reference,
                'amount' => $payload['total'],
                'payer_email' => $user->email,
                'payment_methods' => ['GCASH'],
                'success_redirect_url' => $this->bookingRedirectUrl($payload['branch'], $reference, 'success'),
                'failure_redirect_url' => $this->bookingRedirectUrl($payload['branch'], $reference, 'failed'),
                'metadata' => [
                    'payment_type' => 'BOOKING_FACILITY',
                    'reference_id' => $reference,
                ],
            ]);

        return $this->invoiceResponse($response, $reference);
    }

    private function subscriptionRedirectUrl(string $reference, string $status): string
    {
        return config('app.client_url') . "/product/payment-complete?status={$status}&ref={$reference}";
    }

    private function bookingRedirectUrl(Branch $branch, string $reference, string $status): string
    {
        return config('app.client_url') . "/booking/provider/{$branch->uuid}/payment-complete?status={$status}&ref={$reference}";
    }

    private function invoiceResponse(Response $response, string $reference): JsonResponse
    {
        if ($response->failed()) {
            Cache::forget("xendit_payment_{$reference}");

            return response()->json([
                'success' => false,
                'message' => $response->json('message') ?? 'Unable to start the GCash payment.',
            ], $response->status());
        }

        return response()->json($response->json());
    }
}
