<?php

namespace App\Service;

use App\Enums\BillingIntervalEnum;
use App\Repository\BranchRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\PlanRepository;
use Carbon\Carbon;
use App\Factories\PaymentFactory;
use App\Models\User;
use App\Repository\AgencyRepository;
use App\Repository\LocationRepository;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Service\Utils\AuthGuard;
use App\Service\Utils\NominatimService;
use App\Service\Utils\SupabaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class SubscriptionService
{
    private SubscriptionRepository $subscriptionRepository;
    private PlanRepository $planRepository;
    private BranchRepository $branchRepository;
    private AgencyRepository $agencyRepository;
    private RoleRepository $roleRepository;
    private UserRepository $userRepository;
    private LocationRepository $locationRepository;
    private NominatimService $nominatimService;
    private SupabaseService $supabaseService;
    private string $secretKey;

    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        PlanRepository $planRepository,
        BranchRepository $branchRepository,
        AgencyRepository $agencyRepository,
        RoleRepository $roleRepository,
        UserRepository $userRepository,
        LocationRepository $locationRepository,
        NominatimService $nominatimService,
        SupabaseService $supabaseService,

    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->planRepository = $planRepository;
        $this->branchRepository = $branchRepository;
        $this->agencyRepository = $agencyRepository;
        $this->roleRepository = $roleRepository;
        $this->userRepository = $userRepository;
        $this->locationRepository =  $locationRepository;
        $this->nominatimService = $nominatimService;
        $this->supabaseService = $supabaseService;
        $this->secretKey = config('services.xendit.secret_key');
    }


    public function makeSubscription(array $payload, User $user)
    {
        AuthGuard::requireUser($user);
        $paymentMethod = PaymentFactory::make($payload['payment_method']);
        $subscription = $this->createSubscription($payload);
        return $paymentMethod->subscriptionInvoice($payload, $subscription);
    }

    public function createSubscription(array $payload)
    {
        Log::info($payload);
        $user = Auth::user();
        $plan_code = $payload['plan_code'];
        $billing_interval = BillingIntervalEnum::tryFrom(
            strtoupper($payload['billing_interval'])
        );

        if (!$billing_interval) {
            throw new \Exception(__('Invalid billing interval.'), 422);
        }

        $plan = $this->planRepository->findByField('plan_code', $plan_code);

        if (!$plan) {
            throw new \Exception(__('Plan not found.'), 404);
        }

        $priceField = $billing_interval->loadPriceKey();
        $totalAmount = (float) $plan->{$priceField};

        $endDate = $billing_interval->addTo(Carbon::now())->toDateTimeString();

        $image = null;
        if (!empty($payload['branch_image']) && $payload['branch_image'] instanceof UploadedFile) {
            $image = $this->supabaseService->store($payload['branch_image']);
        }

        return [
            'user' => $user,
            'plan' => $plan,
            'branch' => [
                'name'           => $payload['branch_name'] ?? null,
                'street'         => $payload['branch_street'] ?? null,
                'description'    => $payload['branch_description'] ?? null,
                'city'           => $payload['branch_city'] ?? null,
                'province'       => $payload['branch_province'] ?? null,
                'country'        => $payload['branch_country'] ?? null,
                'contact_number' => $payload['branch_contact_number'] ?? null,
                'image'          => is_array($image) ? ($image['url'] ?? null) : null,
                'setting'        => $payload['branch_settings'] ?? null,
                'latitude'       => $payload['branch_latitude'] ?? null,
                'longitude'      => $payload['branch_longitude'] ?? null,
            ],
            'agency' => [
                'id'             => $payload['agency_id'] ?? null,
                'name'           => $payload['agency_name'] ?? null,
                'description'    => $payload['agency_description'] ?? null,
                'street'         => $payload['agency_street'] ?? null,
                'city'           => $payload['agency_city'] ?? null,
                'province'       => $payload['agency_province'] ?? null,
                'country'        => $payload['agency_country'] ?? null,
                'latitude'       => $payload['agency_latitude'] ?? null,
                'longitude'      => $payload['agency_longitude'] ?? null,
            ],
            'method' => $payload['payment_method'],
            'billing_interval' => $billing_interval->value,
            'total_amount' => $totalAmount,
            'endDate' => $endDate,
            'type' => 'subscription',
            'status' => true
        ];
    }

    public function newSubscriber(array $payload)
    {
        $meta = $payload['metadata'];
        $reference_id = $payload['external_id'];
        $xendit_invoice_id = $payload['xendit_invoice_id'];

        try {

            return DB::transaction(function () use (
                $meta,
                $reference_id,
                $xendit_invoice_id
            ) {

                $plan = $meta['plan'];

                $billing_interval = BillingIntervalEnum::tryFrom(
                    strtoupper($meta['billing_interval'])
                );

                $user = $meta['user'];
                $agency = $meta['agency'];
                $branch = $meta['branch'];
                $endDate = $meta['endDate'];
                $totalAmount = (float) $meta['total_amount'];

                $agencyData = null;
                $agencyId = $agency['id'] ?? null;
                $agencyName = $agency['name'] ?? null;

                if (!empty($agencyId)) {
                    $agencyData = $this->agencyRepository->findAgencyByField('agency_id', $agencyId);
                }

                $agencyLatitude = $agency['latitude'] ?? null;
                $agencyLongitude = $agency['longitude'] ?? null;

                if (empty($agencyLatitude) || empty($agencyLongitude)) {
                    $geo = $this->nominatimService->geocodeAddress(
                        collect($agency)->only([
                            'street',
                            'city',
                            'province',
                            'country'
                        ])->toArray()
                    );

                    $agencyLatitude = $geo['lat'] ?? null;
                    $agencyLongitude = $geo['lng'] ?? null;
                }

                if (empty($agencyData) && !empty($agencyName)) {

                    $agencyLocation = $this->locationRepository->create([
                        'street' => $agency['street'] ?? null,
                        'city' => $agency['city'] ?? null,
                        'province' => $agency['province'] ?? null,
                        'country' => $agency['country'] ?? null,
                        'latitude' => $agencyLatitude,
                        'longitude' => $agencyLongitude,
                    ]);

                    $agencyData = $this->agencyRepository->createAgency([
                        'name' => $agencyName,
                        'description' => $agency['description'] ?? null,
                        'location_id' => $agencyLocation->location_id,
                        'registered_by' => $user['user_id'],
                    ]);
                }

                $branchLatitude = $branch['latitude'] ?? null;
                $branchLongitude = $branch['longitude'] ?? null;

                if (empty($branchLatitude) || empty($branchLongitude)) {

                    $geo = $this->nominatimService->geocodeAddress(
                        collect($branch)->only([
                            'street',
                            'city',
                            'province',
                            'country'
                        ])->toArray()
                    );

                    $branchLatitude = $geo['lat'] ?? null;
                    $branchLongitude = $geo['lng'] ?? null;
                }

                $branchLocation = $this->locationRepository->create([
                    'street' => $branch['street'] ?? null,
                    'city' => $branch['city'] ?? null,
                    'province' => $branch['province'] ?? null,
                    'country' => $branch['country'] ?? null,
                    'latitude' => $branchLatitude,
                    'longitude' => $branchLongitude,
                ]);

                $branchData = $this->branchRepository->create([
                    'owner_user_id' => $user['user_id'],
                    'agency_id' => $agencyData->agency_id ?? null,
                    'location_id' => $branchLocation->location_id,
                    'description' => $branch['description'] ?? null,
                    'name' => $branch['name'] ?? null,
                    'contact_number' => $branch['contact_number'] ?? null,
                    'image' => $branch['image'] ?? null,
                    'settings' => $branch['setting'] ?? null,
                ]);

                if (empty($plan['plan_code'])) {
                    throw new \Exception('Invalid plan type.');
                }

                $subscription = $this->subscriptionRepository->create([
                    'plan_id' => $plan['plan_id'],
                    'branch_id' => $branchData->branch_id,
                    'billing_interval' => $billing_interval->value,
                    'status' => 'active',
                    'start_date' => Carbon::now(),
                    'end_date' => $endDate,
                ]);

                $subscription->subscription_payments()->create([
                    'subscription_id' => $subscription->subscription_id,
                    'xendit_invoice_id' => $xendit_invoice_id,
                    'payment_reference_id' => $reference_id,
                    'price' => $totalAmount,
                ]);

                $role = $this->roleRepository->findByUuid('role_type', 'branch_owner');

                $userModel = $this->userRepository->findByField('user_id', $user['user_id']);

                $userModel->roles()->attach($role->role_id, [
                    'is_active' => true,
                    'branch_id' => $branchData->branch_id,
                ]);

                return response()->json([
                    'status' => true,
                    'message' => __('Subscription created successfully.'),
                ], 201);
            });
        } catch (\Exception $e) {

            Http::withOptions([
                'verify' => false,
            ])->withHeaders([
                'Authorization' => 'Basic ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post(
                "https://api.xendit.co/credit_card_charges/{$xendit_invoice_id}/refunds",
                [
                    'amount' => $meta['total_amount'],
                    'external_id' => (string) Str::uuid(),
                    'metadata' => [
                        'reason' => 'Subscription creation failed — auto refund.',
                    ],
                ]
            );

            return response()->json([
                'status' => false,
                'message' => 'Subscription failed. Your payment has been refunded.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function subscriptionWebhook(object $payload)
    {
        if (
            isset($payload['status'], $payload['external_id']) &&
            ($payload['status'] === 'PAID' || $payload['status'] === 'CAPTURED')
        ) {

            $invoice = Http::withOptions([
                'verify' => false
            ])->withBasicAuth($this->secretKey, '')
                ->get("https://api.xendit.co/v2/invoices/{$payload['id']}")
                ->json();

            $metadata = $invoice['metadata'] ?? [];
            $type = $metadata['type'] ?? null;

            if (! $type) {
                return response()->json([
                    'message' => __('Missing metadata type')
                ], 422);
            }

            if (Str::lower($type) === 'subscription') {
                return $this->newSubscriber([
                    'xendit_invoice_id' => $payload->id,
                    'external_id' => $payload->external_id,
                    'metadata' => $metadata
                ]);
            }

            if (Str::lower($type) === 'renewal') {
                // return $this->renewSubscription([
                //     'xendit_invoice_id' => $payload->id,
                //     'external_id' => $payload->external_id,
                //     'metadata' => $metadata
                // ]);
            }
        }
    }
}
