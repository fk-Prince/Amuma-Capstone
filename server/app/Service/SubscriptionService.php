<?php

namespace App\Service;

use App\Enums\BillingIntervalEnum;
use App\Repository\BranchRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\PlanRepository;
use Carbon\Carbon;
use App\Factories\PaymentFactory;
use App\Guard\AuthGuard;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Repository\AgencyRepository;
use App\Repository\EmployeeRepository;
use App\Repository\LocationRepository;
use App\Repository\ModuleRepository;
use App\Service\External\SupabaseService;
use App\Service\External\XenditService;
use App\Service\Geo\NominatimService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{

    private string $secretKey;

    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private PlanRepository $planRepository,
        private BranchRepository $branchRepository,
        private AgencyRepository $agencyRepository,
        private LocationRepository $locationRepository,
        private NominatimService $nominatimService,
        private EmployeeRepository $employeeRepository,
        private ModuleRepository $moduleRepository
    ) {
        $this->secretKey = config('services.xendit.secret_key');
    }


    public function makeSubscription(array $payload, User $user)
    {
        AuthGuard::requireUser($user);
        $paymentMethod = PaymentFactory::make($payload['payment_method']);
        $subscription = $this->createSubscription($user, $payload);
        return $paymentMethod->subscriptionInvoice($payload, $subscription);
    }

    public function createSubscription(?User $user, array $payload)
    {
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

        $branchImage = null;
        if (!empty($payload['branch_image']) && $payload['branch_image'] instanceof UploadedFile) {
            $branchImage = SupabaseService::store($payload['branch_image']);
        }

        $branchDocument = null;
        if (!empty($payload['branch_document']) && $payload['branch_document'] instanceof UploadedFile) {
            $branchDocument = SupabaseService::store($payload['branch_document']);
        }

        $agencyImage = null;
        if (!empty($payload['agency_image']) && $payload['agency_image'] instanceof UploadedFile) {
            $agencyImage = SupabaseService::store($payload['agency_image']);
        }

        $agencyIdFront = null;
        if (!empty($payload['agency_id_front']) && $payload['agency_id_front'] instanceof UploadedFile) {
            $agencyIdFront = SupabaseService::store($payload['agency_id_front']);
        }

        $agencyIdBack = null;
        if (!empty($payload['agency_id_back']) && $payload['agency_id_back'] instanceof UploadedFile) {
            $agencyIdBack = SupabaseService::store($payload['agency_id_back']);
        }

        $agencyDocument = null;
        if (!empty($payload['agency_document']) && $payload['agency_document'] instanceof UploadedFile) {
            $agencyDocument = SupabaseService::store($payload['agency_document']);
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
                'email'          => $payload['branch_email'] ?? null,
                'contact_number' => $payload['branch_contact_number'] ?? null,
                'image'          => is_array($branchImage) ? ($branchImage['url'] ?? null) : null,
                'document'       => is_array($branchDocument) ? ($branchDocument['url'] ?? null) : null,
                'setting'        => $payload['branch_settings'] ?? null,
                'latitude'       => $payload['branch_latitude'] ?? null,
                'longitude'      => $payload['branch_longitude'] ?? null,
            ],
            'agency' => [
                'id'             => $payload['agency_id'] ?? null,
                'name'           => $payload['agency_name'] ?? null,
                'description'    => $payload['agency_description'] ?? null,
                'email'          => $payload['agency_email'] ?? null,
                'image'          => is_array($agencyImage) ? ($agencyImage['url'] ?? null) : null,
                'id_front'       => is_array($agencyIdFront) ? ($agencyIdFront['url'] ?? null) : null,
                'id_back'        => is_array($agencyIdBack) ? ($agencyIdBack['url'] ?? null) : null,
                'document'       => is_array($agencyDocument) ? ($agencyDocument['url'] ?? null) : null,
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
            'status' => true,
            'payment_type' => $payload['payment_type'] ?? null,
        ];
    }

    public function newSubscriber(array $payload)
    {
        $meta = $payload['metadata'];
        $reference_id = $payload['external_id'];
        $xendit_invoice_id = $payload['xendit_invoice_id'];
        $masked_card_number = $payload['masked_card_number'] ?? null;
        try {

            return DB::transaction(function () use (
                $meta,
                $reference_id,
                $xendit_invoice_id,
                $masked_card_number
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
                        'email' => $agency['email'],
                        'image' => $agency['image'] ?? null,
                        'id_front' => $agency['id_front'] ?? null,
                        'id_back' => $agency['id_back'] ?? null,
                        'document' => $agency['document'] ?? null,
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
                    'agency_id' => $agencyData->agency_id ?? null,
                    'location_id' => $branchLocation->location_id,
                    'description' => $branch['description'] ?? null,
                    'name' => $branch['name'] ?? null,
                    'contact_number' => $branch['contact_number'] ?? null,
                    'image' => $branch['image'] ?? null,
                    'document' => $branch['document'] ?? null,
                    'settings' => $branch['setting'] ?? null,
                    'email' => $branch['email']
                ]);

                if (empty($plan['plan_code'])) {
                    throw new \Exception('Invalid plan type.');
                }

                $subscription = $this->subscriptionRepository->create([
                    'plan_id' => $plan['plan_id'],
                    'branch_id' => $branchData->branch_id,
                    'billing_interval' => $billing_interval->value,
                    'start_date' => Carbon::now(),
                    'end_date' => $endDate,
                ]);

                $subscription->payments()->create([
                    'subscription_id' => $subscription->subscription_id,
                    'xendit_invoice_id' => $xendit_invoice_id,
                    'payment_reference_id' => $reference_id,
                    'masked_card_number' => $masked_card_number,
                    'price' => $totalAmount,
                    'status' => SubscriptionPayment::STATUS_PAID,
                ]);

                $employee = $this->employeeRepository->findEmployeeByFields([
                    ['user_id', '=', $user['user_id']],
                ]);

                if (!$employee) {
                    $employee = $this->employeeRepository->createEmployee([
                        'user_id'    => $user['user_id'],
                        'first_name' => $user['first_name'],
                        'last_name'  => $user['last_name'],
                        'avatar'     => $user['avatar'] ?? null,
                    ]);
                }
                $employee->employeeBranch()->create([
                    'role_name' => 'branch_owner',
                    'branch_id'   => $branchData->branch_id,
                    'employee_id' => $employee->employee_id,
                ]);

                $modules = $this->moduleRepository->getAllModules();

                foreach ($modules as $module) {
                    $employee->permissions()->create([
                        'module_id'   => $module->module_id,
                        'branch_id'   => $branchData->branch_id,
                        'employee_id' => $employee->employee_id,
                    ]);
                }

                return response()->json([
                    'status' => true,
                    'message' => __('Subscription created successfully.'),
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('Subscription creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            Http::withOptions([
                'verify' => false,
            ])->withBasicAuth($this->secretKey, '')
                ->post('https://api.xendit.co/refunds', [
                    'invoice_id'   => $xendit_invoice_id,
                    'reference_id' => (string) Str::uuid(),
                    'amount'       => $meta['total_amount'],
                    'reason'       => 'CANCELLATION',
                    'metadata' => [
                        'message' => 'Subscription creation failed.',
                    ],
                ]);

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
                    'metadata' => $metadata,
                    'masked_card_number' => $invoice['masked_card_number']
                        ?? $invoice['credit_card_charge']['masked_card_number']
                        ?? null,
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

    public function subscriptionList(array $payload)
    {
        $subscriptions = $this->subscriptionRepository->paginate($payload);
        return SubscriptionResource::collection($subscriptions);
    }

    public function overview(array $payload)
    {
        if ($payload['action'] === 'overview') {
            return $this->subscriptionRepository->overview();
        } else if ($payload['action'] === 'overview_subscription') {
            return $this->subscriptionRepository->overviewSubscription();
        }
    }

    public function approve(array $payload)
    {
        return DB::transaction(function () use ($payload) {

            $subscription = $this->subscriptionRepository->findByFields([
                ['uuid', '=', $payload['subscription_uuid']],
            ]);

            if (!$subscription) {
                throw new Exception('Subscription not found.', 404);
            }

            if ($subscription->status !== 'pending') {
                throw new Exception("Only pending subscriptions can be approved (current status: {$subscription->status}).", 404);
            }

            $subscription->load('branch.agencies');

            $branch = $subscription->branch;
            $agency = $branch?->agencies;

            if (! $branch) {
                throw new Exception('Branch not found for this subscription.', 404);
            }

            if (! $branch->is_verified) {
                $branch->update(['is_verified' => true]);
            }

            if ($agency && ! $agency->is_verified) {
                $agency->update(['is_verified' => true]);
            }

            $startDate = Carbon::now();
            $endDate = $subscription->billing_interval === 'YEARLY'
                ? $startDate->copy()->addYear()
                : $startDate->copy()->addMonth();

            $subscription->update([
                'status' => 'active',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            return response()->json([
                'message' => '',
                'data' => $subscription->fresh(['branch.agencies'])
            ]);
        });
    }

    public function reject(array $payload)
    {
        return DB::transaction(function () use ($payload) {

            $subscription = $this->subscriptionRepository->findByFields([
                ['uuid', '=', $payload['subscription_uuid']],
            ]);

            if (!$subscription) {
                throw new Exception('Subscription not found.', 404);
            }

            if ($subscription->status !== 'pending') {
                throw new Exception("Only pending subscriptions can be approved (current status: {$subscription->status}).", 404);
            }
            $subscription->load([
                'branch.agencies',
                'payments',
            ]);

            $payment = $subscription->payments
                ->where('status', SubscriptionPayment::STATUS_PAID)
                ->sortByDesc('created_at')
                ->first();


            if (!$payment) {
                throw new Exception('No successful payment found for this subscription.',  404);
            }

            $refunded = XenditService::refundXenditPayment(
                $payment->xendit_invoice_id,
                (float) $payment->price
            );

            if (!$refunded) {
                throw new Exception('Subscription cannot be rejected because the payment refund failed.',);
            }

            $subscription->update([
                'status' => Subscription::STATUS_REJECTED,
            ]);

            $payment->update([
                'status' => SubscriptionPayment::STATUS_REFUNDED,
            ]);


            return response()->json([
                'message' => '',
                'data' => $subscription->fresh(['branch.agencies'])
            ]);
        });
    }
}
