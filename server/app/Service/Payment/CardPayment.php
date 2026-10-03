<?php

namespace App\Service\Payment;

use App\Exceptions\ExternalServiceException;
use App\Interfaces\IFacilityPayment;
use App\Interfaces\ISubscriptionPayment;
use App\Service\BookingService;
use App\Service\SubscriptionService;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CardPayment implements ISubscriptionPayment, IFacilityPayment
{
    private string $secretKey;
    public function __construct(private SubscriptionService $subscriptionService,)
    {
        $this->secretKey = config('services.xendit.secret_key');
    }

    public function subscriptionInvoice(array $payload, array $subscription)
    {
        $user      = Auth::user();
        $reference = (string) Str::uuid();

        try {
            $response = Http::withOptions([
                'verify' => false
            ])->withBasicAuth($this->secretKey, '')
                ->post('https://api.xendit.co/credit_card_charges', [
                    'token_id'          => $payload['token_id'],
                    'authentication_id' => $payload['authentication_id'],
                    'capture'           => true,
                    'descriptor'        => 'subscription',
                    'currency'          => 'PHP',
                    'external_id'       => $reference,
                    'amount'            => $subscription['total_amount'],
                    'payer_email'       => $user->email,
                    'payment_methods'   => ['CREDIT-CARD'],
                    'metadata'          => [
                        'type'             => $subscription['type'],
                        'plan'             => $subscription['plan'],
                        'user'             => $subscription['user'],
                        'branch'           => $subscription['branch'],
                        'agency'           => $subscription['agency'],
                        'payment_method'   => $subscription['method'],
                        'total_amount'     => $subscription['total_amount'],
                        'endDate'          => $subscription['endDate'],
                        'mode'             => $subscription['mode'] ?? null,
                        'subscription_uuid' => $subscription['subscription_uuid'] ?? null,
                        'action'           => $subscription['action'] ?? null,
                        'plan_starts_at'   => $subscription['plan_starts_at'] ?? null,
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('Xendit subscription charge failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => ExternalServiceException::PAYMENT_FAILED,
                ], 502);
            }

            $charge = $response->json();

            $result = [
                'metadata'          => $charge['metadata'] ?? [],
                'external_id'       => $charge['external_id'] ?? null,
                'xendit_invoice_id' => $charge['id'] ?? null,
                'masked_card_number' => $charge['masked_card_number'] ?? null,
            ];

            return $this->subscriptionService->settlePayment($result);
        } catch (ConnectionException $e) {
            report($e);

            return response()->json([
                'message' => ExternalServiceException::PAYMENT_FAILED,
            ], 502);
        }
    }


    public function facilityBilling(array $payload)
    {
        $user      = Auth::user();
        $reference = (string) Str::uuid();
        try {
            $response = Http::withOptions([
                'verify' => false
            ])->withBasicAuth($this->secretKey, '')
                ->post('https://api.xendit.co/credit_card_charges', [
                    'token_id'          => $payload['token_id'],
                    'authentication_id' => $payload['authentication_id'],
                    'capture'           => true,
                    'descriptor'        => 'subscription',
                    'currency'          => 'PHP',
                    'external_id'       => $reference,
                    'amount'            => $payload['total'],
                    'payer_email'       => $user->email,
                    'payment_methods'   => ['CREDIT-CARD'],
                    'metadata'          => $payload
                ]);

            if ($response->failed()) {
                Log::warning('Xendit facility charge failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => ExternalServiceException::PAYMENT_FAILED,
                ], 502);
            }

            $charge = $response->json();
            return  [
                'metadata'          => $charge['metadata'] ?? [],
                'masked_card_number' => $charge['masked_card_number'] ?? null,
                'external_id'       => $charge['external_id'] ?? null,
                'xendit_invoice_id' => $charge['id'] ?? null,
                'total'             => $payload['total']
            ];
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'message' => ExternalServiceException::PAYMENT_FAILED,
            ], 502);
        }
    }
}
