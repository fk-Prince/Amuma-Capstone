<?php

namespace App\Service;

use App\Enums\BillingIntervalEnum;
use App\Events\NotificationEvent;
use App\Mail\SubscriptionPurchasedMailer;
use App\Repository\BranchRepository;
use App\Repository\NotificationRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\PlanRepository;
use Carbon\Carbon;
use App\Factories\PaymentFactory;
use App\Guard\AuthGuard;
use App\Http\Resources\SubscriptionResource;
use App\Models\Agency;
use App\Models\BranchSubscription;
use App\Models\EmployeePermission;
use App\Models\PlatformAdmin;
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
use Illuminate\Support\Facades\Mail;
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
        private ModuleRepository $moduleRepository,
        private NotificationRepository $notificationRepository
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

    public function makeRenewal(array $payload, User $user)
    {
        AuthGuard::requireUser($user);
        $paymentMethod = PaymentFactory::make($payload['payment_method']);
        $renewal = $this->createRenewal($user, $payload);
        return $paymentMethod->subscriptionInvoice($payload, $renewal);
    }


    public function createRenewal(?User $user, array $payload)
    {
        $subscription = $this->subscriptionRepository
            ->findLatestForBranch($payload['branch_id']);

        if (!$subscription) {
            throw new Exception(__('This branch has no subscription to renew.'), 404);
        }


        if ($subscription->pendingPlanIsDue()) {
            $subscription->update([
                'plan_id' => $subscription->pending_plan_id,
                'pending_plan_id' => null,
                'pending_plan_starts_at' => null,
            ]);

            $subscription->refresh();
        }

        if ($subscription->pending_plan_id) {
            throw new Exception(
                __(':plan is already paid for and starts on :date. You can renew again once it takes over.', [
                    'plan' => $subscription->pendingPlan?->name,
                    'date' => Carbon::parse($subscription->pending_plan_starts_at)->toFormattedDateString(),
                ]),
                422
            );
        }

        $planCode = $payload['plan_code'] ?? $subscription->plans?->plan_code;
        $plan = $this->planRepository->findByField('plan_code', $planCode);

        if (!$plan) {
            throw new Exception(__('Plan not found.'), 404);
        }

        $interval = BillingIntervalEnum::tryFrom(
            strtoupper($payload['billing_interval'] ?? $subscription->billing_interval)
        );

        if (!$interval) {
            throw new Exception(__('Invalid billing interval.'), 422);
        }


        $currentEnd = $subscription->end_date
            ? Carbon::parse($subscription->end_date)
            : Carbon::now();

        $isUpgrade = $plan->plan_id !== $subscription->plan_id;

        $startNow = $isUpgrade
            && ($payload['upgrade_timing'] ?? 'after') === 'now';

        $extendFrom = ($startNow || !$currentEnd->isFuture())
            ? Carbon::now()
            : $currentEnd;

        return [
            'user' => $user,
            'plan' => $plan,
            'branch' => ['branch_id' => $payload['branch_id']],
            'agency' => [],
            'subscription_uuid' => $subscription->uuid,
            'method' => $payload['payment_method'],
            'billing_interval' => $interval->value,
            'total_amount' => (float) $plan->{$interval->loadPriceKey()},
            'endDate' => $interval->addTo($extendFrom)->toDateTimeString(),
            'is_upgrade' => $isUpgrade,
            'upgrade_starts_now' => $startNow,
            'upgrade_starts_at' => $isUpgrade && !$startNow
                ? $currentEnd->toDateString()
                : null,
            'type' => 'renewal',
            'status' => true,
            'payment_type' => 'RENEWAL',
        ];
    }

    public function renewSubscriber(array $payload)
    {
        $meta = $payload['metadata'];

        return DB::transaction(function () use ($payload, $meta) {
            $subscription = $this->subscriptionRepository->findByFields([
                ['uuid', '=', $meta['subscription_uuid']],
            ]);

            if (!$subscription) {
                throw new Exception(__('Subscription not found.'), 404);
            }

            $paidPlanId = $meta['plan']['plan_id'] ?? $subscription->plan_id;
            $startsAt = $meta['upgrade_starts_at'] ?? null;

            $changes = [
                'billing_interval' => $meta['billing_interval'],
                'end_date' => $meta['endDate'],
                'status' => Subscription::STATUS_ACTIVE,
            ];

            if ($startsAt) {
                $changes['pending_plan_id'] = $paidPlanId;
                $changes['pending_plan_starts_at'] = $startsAt;
            } else {
                $changes['plan_id'] = $paidPlanId;
                $changes['pending_plan_id'] = null;
                $changes['pending_plan_starts_at'] = null;

                if (!empty($meta['upgrade_starts_now'])) {
                    $changes['start_date'] = Carbon::now();
                }
            }

            $subscription->update($changes);

            $subscription->payments()->create([
                'subscription_id' => $subscription->subscription_id,
                'plan_id' => $paidPlanId,
                'xendit_invoice_id' => $payload['xendit_invoice_id'] ?? null,
                'payment_reference_id' => $payload['external_id'] ?? null,
                'masked_card_number' => $payload['masked_card_number'] ?? null,
                'price' => $meta['total_amount'],
                'status' => SubscriptionPayment::STATUS_PAID,
            ]);

            return response()->json([
                'status' => true,
                'message' => __('Subscription renewed successfully.'),
                'subscription' => [
                    'uuid' => $subscription->uuid,
                    'status' => $subscription->status,
                    'billing_interval' => $subscription->billing_interval,
                    'start_date' => $subscription->start_date,
                    'end_date' => $subscription->end_date,
                ],
            ], 200);
        });
    }

    public function applyPendingPlan(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $subscription = $this->subscriptionRepository
                ->findLatestForBranch($payload['branch_id']);

            if (!$subscription) {
                throw new Exception(__('This branch has no subscription.'), 404);
            }

            if (!$subscription->pending_plan_id) {
                throw new Exception(__('There is no queued upgrade to apply.'), 422);
            }

            $plan = $subscription->pendingPlan;
            $today = Carbon::now()->startOfDay();
            $startsAt = Carbon::parse($subscription->pending_plan_starts_at)->startOfDay();

            $forfeited = $startsAt->isAfter($today)
                ? $today->diffInDays($startsAt)
                : 0;

            $subscription->update([
                'plan_id' => $subscription->pending_plan_id,
                'pending_plan_id' => null,
                'pending_plan_starts_at' => null,
                'start_date' => Carbon::now(),
                'end_date' => $subscription->end_date
                    ? Carbon::parse($subscription->end_date)->subDays($forfeited)
                    : $subscription->end_date,
            ]);

            return response()->json([
                'status' => true,
                'message' => __(':plan is active now.', ['plan' => $plan?->name]),
                'forfeited_days' => $forfeited,
                'subscription' => [
                    'uuid' => $subscription->uuid,
                    'status' => $subscription->status,
                    'billing_interval' => $subscription->billing_interval,
                    'start_date' => $subscription->start_date,
                    'end_date' => $subscription->end_date,
                    'plan' => [
                        'plan_id' => $plan?->plan_id,
                        'name' => $plan?->name,
                        'plan_code' => $plan?->plan_code,
                    ],
                    'pending_plan' => null,
                ],
            ], 200);
        });
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

                $needsAgency = empty($agencyData) && !empty($agencyName);

                if ($needsAgency && (empty($agencyLatitude) || empty($agencyLongitude))) {
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

                if ($needsAgency) {

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
                    'agency_id' => $agencyData->agency_id ?? null,
                    'billing_interval' => $billing_interval->value,
                    'start_date' => Carbon::now(),
                    'end_date' => $endDate,
                ]);

                // 1st brnac
                BranchSubscription::create([
                    'subscription_id' => $subscription->subscription_id,
                    'branch_id' => $branchData->branch_id,
                    'status' => BranchSubscription::STATUS_PENDING,
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
                    EmployeePermission::updateOrCreate(
                        [
                            'employee_id' => $employee->employee_id,
                            'branch_id'   => $branchData->branch_id,
                            'module_id'   => $module->module_id,
                        ],
                        [
                            'can_read'    => true,
                            'can_create'  => true,
                            'can_update'  => true,
                            'can_approve' => true,
                            'can_assign'  => true,
                        ]
                    );
                }

                if (!empty($user['email'])) {
                    Mail::to($user['email'])->send(new SubscriptionPurchasedMailer(
                        recipientName: trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'there',
                        planName: $plan['name'] ?? $plan['plan_code'],
                        branchName: $branchData->name,
                        amount: $totalAmount,
                        billingInterval: $billing_interval->value,
                    ));
                }

                $adminMessage = "New subscription request from {$branchData->name} is awaiting your review.";

                $admins = User::whereIn(
                    'user_id',
                    PlatformAdmin::pluck('user_id')
                )->get(['user_id', 'uuid']);

                foreach ($admins as $admin) {
                    $this->notificationRepository->create([
                        'branch_id' => $branchData->branch_id,
                        'to_user_id' => $admin->user_id,
                        'from_user_id' => $user['user_id'],
                        'message_type' => 'Subscription',
                        'message' => $adminMessage,
                    ]);

                    event(new NotificationEvent(
                        $admin->uuid,
                        $branchData->uuid,
                        $adminMessage,
                        (string) $subscription->subscription_id,
                        'Subscription',
                        $subscription
                    ));
                }

                return response()->json([
                    'status' => true,
                    'message' => __('Subscription created successfully.'),
                    'branch' => [
                        'branch_id' => $branchData->branch_id,
                        'uuid' => $branchData->uuid,
                        'name' => $branchData->name,
                        'description' => $branchData->description,
                        'image' => $branchData->image,
                        'is_verified' => $branchData->is_verified,
                        'contact_number' => $branchData->contact_number,
                        'email' => $branchData->email,
                        'location' => $branchLocation ? [
                            'street' => $branchLocation->street,
                            'city' => $branchLocation->city,
                            'province' => $branchLocation->province,
                            'country' => $branchLocation->country,
                            'full_address' => $branchLocation->full_address,
                        ] : null,
                        'agency' => $agencyData ? [
                            'agency_id' => $agencyData->agency_id,
                            'name' => $agencyData->name,
                        ] : null,
                        'rooms_count' => 0,
                        'staff_count' => 1,
                        'patients_count' => 0,
                    ],
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


    public function createBranchWithinCapacity(array $payload, User $user)
    {
        if (empty($payload['agency_id'])) {
            throw new Exception('An agency is required to add a branch.', 422);
        }

        return DB::transaction(function () use ($payload, $user) {

            $agency = Agency::where('agency_id', $payload['agency_id'])
                ->lockForUpdate()
                ->first();

            if (!$agency) {
                throw new Exception('Agency not found.', 404);
            }

            $subscription = $this->subscriptionRepository->findSubscriptionWithRoom(
                $agency->agency_id,
                $payload['subscription_uuid'] ?? null
            );

            if (!$subscription) {
                throw new Exception(
                    'That subscription has no free branch slots. Pick another or purchase a new subscription.',
                    409
                );
            }

            $branchLatitude = $payload['branch_latitude'] ?? null;
            $branchLongitude = $payload['branch_longitude'] ?? null;

            if (empty($branchLatitude) || empty($branchLongitude)) {
                $geo = $this->nominatimService->geocodeAddress([
                    'street' => $payload['branch_street'] ?? null,
                    'city' => $payload['branch_city'] ?? null,
                    'province' => $payload['branch_province'] ?? null,
                    'country' => $payload['branch_country'] ?? null,
                ]);

                $branchLatitude = $geo['lat'] ?? null;
                $branchLongitude = $geo['lng'] ?? null;
            }

            $branchLocation = $this->locationRepository->create([
                'street' => $payload['branch_street'] ?? null,
                'city' => $payload['branch_city'] ?? null,
                'province' => $payload['branch_province'] ?? null,
                'country' => $payload['branch_country'] ?? null,
                'latitude' => $branchLatitude,
                'longitude' => $branchLongitude,
            ]);

            $branchData = $this->branchRepository->create([
                'agency_id' => $agency->agency_id,
                'location_id' => $branchLocation->location_id,
                'description' => $payload['branch_description'] ?? null,
                'name' => $payload['branch_name'] ?? null,
                'contact_number' => $payload['branch_contact_number'] ?? null,
                'image' => $payload['branch_image'] ?? null,
                'document' => $payload['branch_document'] ?? null,
                'settings' => $payload['branch_settings'] ?? null,
                'email' => $payload['branch_email'],
            ]);

            $link = BranchSubscription::create([
                'subscription_id' => $subscription->subscription_id,
                'branch_id' => $branchData->branch_id,
                'status' => BranchSubscription::STATUS_PENDING,
            ]);

            $employee = $this->employeeRepository->findEmployeeByFields([
                ['user_id', '=', $user->user_id],
            ]);

            if (!$employee) {
                $employee = $this->employeeRepository->createEmployee([
                    'user_id'    => $user->user_id,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'avatar'     => $user->avatar,
                ]);
            }

            $employee->employeeBranch()->create([
                'role_name' => 'branch_owner',
                'branch_id'   => $branchData->branch_id,
                'employee_id' => $employee->employee_id,
            ]);

            foreach ($this->moduleRepository->getAllModules() as $module) {
                EmployeePermission::updateOrCreate(
                    [
                        'employee_id' => $employee->employee_id,
                        'branch_id'   => $branchData->branch_id,
                        'module_id'   => $module->module_id,
                    ],
                    [
                        'can_read'    => true,
                        'can_create'  => true,
                        'can_update'  => true,
                        'can_approve' => true,
                        'can_assign'  => true,
                    ]
                );
            }

            $adminMessage = "New branch request from {$branchData->name} (included in an existing subscription) is awaiting your review.";

            $admins = User::whereIn(
                'user_id',
                PlatformAdmin::pluck('user_id')
            )->get(['user_id', 'uuid']);

            foreach ($admins as $admin) {
                $this->notificationRepository->create([
                    'branch_id' => $branchData->branch_id,
                    'to_user_id' => $admin->user_id,
                    'from_user_id' => $user->user_id,
                    'message_type' => 'Subscription',
                    'message' => $adminMessage,
                ]);

                event(new NotificationEvent(
                    $admin->uuid,
                    $branchData->uuid,
                    $adminMessage,
                    (string) $subscription->subscription_id,
                    'Subscription',
                    $subscription
                ));
            }

            return response()->json([
                'status' => true,
                'message' => __('Branch added and sent for review.'),
                'branch' => [
                    'branch_id' => $branchData->branch_id,
                    'uuid' => $branchData->uuid,
                    'name' => $branchData->name,
                    'description' => $branchData->description,
                    'image' => $branchData->image,
                    'is_verified' => $branchData->is_verified,
                    'contact_number' => $branchData->contact_number,
                    'email' => $branchData->email,
                    'location' => [
                        'street' => $branchLocation->street,
                        'city' => $branchLocation->city,
                        'province' => $branchLocation->province,
                        'country' => $branchLocation->country,
                        'full_address' => $branchLocation->full_address,
                    ],
                    'agency' => [
                        'agency_id' => $agency->agency_id,
                        'name' => $agency->name,
                    ],
                    'rooms_count' => 0,
                    'staff_count' => 1,
                    'patients_count' => 0,
                ],
            ], 201);
        });
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
            return $this->subscriptionRepository->overview(
                (int) ($payload['revenue_months'] ?? 6),
                isset($payload['revenue_year']) ? (int) $payload['revenue_year'] : null
            );
        } else if ($payload['action'] === 'overview_subscription') {
            return $this->subscriptionRepository->overviewSubscription();
        }
    }

    public function approve(array $payload)
    {
        return DB::transaction(function () use ($payload) {

            $link = $this->resolveBranchLink($payload);

            if ($link->status !== BranchSubscription::STATUS_PENDING) {
                throw new Exception("Only pending branches can be approved (current status: {$link->status}).", 404);
            }

            $link->load(['branch.agencies', 'subscription']);

            $branch = $link->branch;
            $agency = $branch?->agencies;
            $subscription = $link->subscription;

            if (! $branch) {
                throw new Exception('Branch not found for this request.', 404);
            }

            $link->update(['status' => BranchSubscription::STATUS_APPROVED]);

            if (! $branch->is_verified) {
                $branch->update(['is_verified' => true]);
            }

            if ($agency && ! $agency->is_verified) {
                $agency->update(['is_verified' => true]);
            }


            if ($subscription && $subscription->status === Subscription::STATUS_PENDING) {
                $startDate = Carbon::now();

                $subscription->update([
                    'status' => Subscription::STATUS_ACTIVE,
                    'start_date' => $startDate,
                    'end_date' => $subscription->billing_interval === 'YEARLY'
                        ? $startDate->copy()->addYear()
                        : $startDate->copy()->addMonth(),
                ]);
            }

            return response()->json([
                'message' => '',
                'data' => $link->fresh(['branch.agencies', 'subscription.plans']),
            ]);
        });
    }

    private function resolveBranchLink(array $payload): BranchSubscription
    {
        $uuid = $payload['branch_subscription_uuid']
            ?? $payload['subscription_uuid']
            ?? null;

        if (!$uuid) {
            throw new Exception('No branch request was specified.', 422);
        }

        $link = BranchSubscription::where('uuid', $uuid)->first();

        if (!$link) {
            $subscription = Subscription::where('uuid', $uuid)->first();

            $link = $subscription
                ? BranchSubscription::where('subscription_id', $subscription->subscription_id)
                ->orderBy('created_at')
                ->first()
                : null;
        }

        if (!$link) {
            throw new Exception('Branch request not found.', 404);
        }

        return $link;
    }

    public function reject(array $payload)
    {
        return DB::transaction(function () use ($payload) {

            $link = $this->resolveBranchLink($payload);

            if ($link->status !== BranchSubscription::STATUS_PENDING) {
                throw new Exception("Only pending branches can be rejected (current status: {$link->status}).", 404);
            }

            $link->load(['branch.agencies', 'subscription.payments']);

            $subscription = $link->subscription;

            $link->update(['status' => BranchSubscription::STATUS_REJECTED]);

            $remaining = BranchSubscription::where('subscription_id', $link->subscription_id)
                ->where('status', '!=', BranchSubscription::STATUS_REJECTED)
                ->count();

            if ($remaining === 0 && $subscription) {
                $payment = $subscription->payments
                    ->where('status', SubscriptionPayment::STATUS_PAID)
                    ->sortByDesc('created_at')
                    ->first();

                if ($payment) {
                    $refunded = XenditService::refundXenditPayment(
                        $payment->xendit_invoice_id,
                        (float) $payment->price
                    );

                    if (!$refunded) {
                        throw new Exception('Subscription cannot be rejected because the payment refund failed.',);
                    }

                    $payment->update([
                        'status' => SubscriptionPayment::STATUS_REFUNDED,
                    ]);
                }

                $subscription->update([
                    'status' => Subscription::STATUS_REJECTED,
                ]);
            }

            return response()->json([
                'message' => '',
                'data' => $link->fresh(['branch.agencies', 'subscription.plans']),
            ]);
        });
    }
}
