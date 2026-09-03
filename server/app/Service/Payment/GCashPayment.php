<?php

namespace App\Service\Payment;

use App\Http\Resources\UserResource;
use App\Interfaces\IFacilityPayment;
use App\Interfaces\ISubscriptionPayment;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        $response = Http::withOptions([
            'verify' => false
        ])->withBasicAuth($this->secretKey, '')
            ->post('https://api.xendit.co/v2/invoices', [
                'external_id' =>  $reference,
                'amount' => $subscription['total_amount'],
                'payer_email' => $user->email,
                'payment_methods' => ['GCASH'],
                'success_redirect_url' => config('app.client_url') . '/product/subscription-summary?status=success',
                'failure_redirect_url' => config('app.client_url') . '/product/subscription-summary?status=failed',
                'metadata' => [
                    'type' => $subscription['type'],
                    'plan' => $subscription['plan'],
                    'user' => $subscription['user'],
                    'branch' => $subscription['branch'],
                    'agency' => $subscription['agency'],
                    'billing_interval' => $subscription['billing_interval'],
                    'total_amount' => $subscription['total_amount'],
                    'endDate' => $subscription['endDate'],
                    'payment_type' => $subscription['payment_type'],
                    // renewSubscriber() looks the subscription up by this, and
                    // needs the upgrade keys to know when to apply the plan.
                    'subscription_uuid' => $subscription['subscription_uuid'] ?? null,
                    'is_upgrade' => $subscription['is_upgrade'] ?? false,
                    'upgrade_starts_now' => $subscription['upgrade_starts_now'] ?? false,
                    'upgrade_starts_at' => $subscription['upgrade_starts_at'] ?? null,
                ]
            ]);
        return response()->json($response->json());
    }

    public function facilityBilling(array $payload)
    {
        $user = Auth::user();
        $reference = (string) Str::uuid();
        Cache::put(
            "xendit_payment_{$reference}",
            [
                ...$payload,
                'user' => $user,
            ],
            now()->addHours(24)
        );

        $payload['total_amount'] = 5000; // TODO: TO BE CHANGE

        $response = Http::withOptions([
            'verify' => false
        ])->withBasicAuth($this->secretKey, '')
            ->post('https://api.xendit.co/v2/invoices', [
                'external_id' =>  $reference,
                'amount' =>  $payload['total_amount'],
                'payer_email' => $user->email,
                'payment_methods' => ['GCASH'],
                'success_redirect_url' => config('app.client_url') . '/booking/provider/' . $payload['branch_uuid'] . '/success',
                // 'failure_redirect_url' => config('app.client_url') . '/product/subscription-summary?status=failed', // TODO: TO BE CHANGE
                'metadata' => [
                    'payment_type' => $payload['payment_type'],
                    'reference_id' => $reference,
                ],
            ]);

        return response()->json($response->json());
    }
}
