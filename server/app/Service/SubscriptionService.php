<?php

namespace App\Service;

use App\Enums\RoleEnum;
use App\Events\NotificationEvent;
use App\Mail\BranchRejectedMailer;
use App\Mail\SubscriptionPurchasedMailer;
use App\Repository\BranchRepository;
use App\Repository\NotificationRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\PlanRepository;
use App\Repository\VerificationLogRepository;
use Carbon\Carbon;
use App\Factories\PaymentFactory;
use App\Guard\AuthGuard;
use App\Http\Resources\SubscriptionResource;
use App\Models\Agency;
use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Models\EmployeePermission;
use App\Models\Plan;
use App\Models\PlatformAdmin;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Models\VerificationLog;
use App\Repository\AgencyRepository;
use App\Repository\EmployeeRepository;
use App\Repository\LocationRepository;
use App\Repository\ModuleRepository;
use App\Service\External\SupabaseService;
use App\Service\External\XenditService;
use App\Utils\MoneyWords;
use App\Service\Geo\NominatimService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    private const PAYMENT_TIME_LIMIT = 120;

    private const ACTION_RENEW = 'renew';
    private const ACTION_UPGRADE = 'upgrade';
    private const ACTION_RESUBSCRIBE = 'resubscribe';
    private const ACTION_ADDITIONAL_BRANCH = 'additional_branch';

    private const PAYMENT_TYPE_ADDITIONAL_BRANCH = 'additional_branch';

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
        private NotificationRepository $notificationRepository,
        private VerificationLogRepository $verificationLogRepository
    ) {
        $this->secretKey = config('services.xendit.secret_key');
    }

    private function grantOwnerPermissions(int $employeeId, int $branchId): void
    {
        $owner = RoleEnum::BranchManager->permissions();
        foreach ($this->moduleRepository->getAllModules() as $module) {
            EmployeePermission::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'branch_id'   => $branchId,
                    'module_id'   => $module->module_id,
                ],
                EmployeePermission::grantColumns($owner[$module->module_name] ?? [])
            );
        }
    }


    private function ensureNotTesting(int|string|null $agencyId): void
    {
        if ($this->subscriptionRepository->agencyIsTesting($agencyId)) {
            throw new Exception(__('Branches can be added once your free testing ends.'), 422);
        }
    }

    private function ensureNoSubscription(int|string|null $agencyId): void
    {
        if ($agencyId && $this->subscriptionRepository->agencyHasSubscription($agencyId)) {
            throw new Exception(__('This agency already has a subscription. Add a branch to it instead.'), 422);
        }
    }

    public function makeSubscription(array $payload, User $user)
    {
        AuthGuard::requireUser($user);
        $this->ensureNoSubscription($payload['agency_id'] ?? null);
        set_time_limit(self::PAYMENT_TIME_LIMIT);
        $subscription = $this->createSubscription($user, $payload);

        $paymentMethod = PaymentFactory::make($payload['payment_method']);
        return $paymentMethod->subscriptionInvoice($payload, $subscription);
    }

    public function makeRenewal(array $payload, User $user)
    {
        AuthGuard::requireUser($user);
        set_time_limit(self::PAYMENT_TIME_LIMIT);
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


        if ($subscription->isCancelled()) {
            return $this->createResubscription($user, $subscription, $payload);
        }

        if ($subscription->pendingPlanIsDue()) {
            $subscription->update($subscription->pendingPlanChanges());

            $subscription->refresh();
        }

        $current = $subscription->plans;

        if (!empty($payload['plan_type']) && $payload['plan_type'] !== $current?->type) {
            throw new Exception(__('The plan type of a subscription can\'t be changed.'), 422);
        }

        $plan = $this->planRepository->findByCodeAndType(
            $payload['plan_code'] ?? $current?->plan_code,
            $current?->type
        );

        if (!$plan) {
            throw new Exception(__('Plan not found.'), 404);
        }

        $detail = [
            'user' => $user,
            'plan' => $plan,
            'branch' => ['branch_id' => $payload['branch_id']],
            'agency' => [],
            'subscription_uuid' => $subscription->uuid,
            'method' => $payload['payment_method'],
            'type' => 'renewal',
            'status' => true,
            'payment_type' => 'RENEWAL',
        ];

        $changesPlan = $plan->plan_id !== $current->plan_id;
        $isUpgrade = $changesPlan && $plan->plan_code === Plan::CODE_HYBRID;

        if ($changesPlan && !$isUpgrade && $current->plan_code !== Plan::CODE_HYBRID) {
            throw new Exception(__('This plan can only be upgraded to Hybrid.'), 422);
        }

        if ($isUpgrade && $subscription->canUpgrade()) {
            return [
                ...$detail,
                'action' => self::ACTION_UPGRADE,
                'total_amount' => Subscription::proratedUpgrade(
                    $current,
                    $plan,
                    $subscription->upgradeMonths(),
                    $subscription->additionalBranchCount()
                ),
                'endDate' => $subscription->end_date->toDateString(),
            ];
        }

        if ($subscription->isTest()) {
            throw new Exception(__('Your paid year is already paid for and starts when free testing ends.'), 422);
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

        if (!$subscription->isRenewable()) {
            throw new Exception(
                __($changesPlan
                    ? 'Downgrades happen only at renewal, which opens on :date, :days days before this subscription ends.'
                    : 'Renewal opens on :date, :days days before this subscription ends.', [
                    'date' => $subscription->renewalOpensAt()->toFormattedDateString(),
                    'days' => Subscription::RENEWAL_WINDOW_DAYS,
                ]),
                422
            );
        }

        $currentEnd = Carbon::parse($subscription->end_date);
        $extendsFrom = $currentEnd->isFuture() ? $currentEnd : Carbon::now();

        return [
            ...$detail,
            'action' => self::ACTION_RENEW,
            'total_amount' => $subscription->renewalAmount($plan),
            'endDate' => Subscription::termEnd($extendsFrom)->toDateTimeString(),
            'plan_starts_at' => $changesPlan && $currentEnd->isFuture()
                ? $currentEnd->toDateString()
                : null,
        ];
    }

    private function createResubscription(?User $user, Subscription $subscription, array $payload): array
    {
        $planType = $payload['plan_type'] ?? $subscription->plans?->type;

        if (!in_array($planType, Plan::TYPES, true)) {
            throw new Exception(__('Invalid plan type.'), 422);
        }

        $plan = $this->planRepository->findByCodeAndType(
            $payload['plan_code'] ?? $subscription->plans?->plan_code,
            $planType
        );

        if (!$plan) {
            throw new Exception(__('Plan not found.'), 404);
        }

        $branches = $subscription->branchLinks()
            ->where('status', '!=', BranchSubscription::STATUS_REJECTED)
            ->where('type', BranchSubscription::TYPE_INCLUDED)
            ->count();

        if ($branches > $plan->branch_limit) {
            throw new Exception(
                __(':type covers :limit branch(es), but this subscription has :count.', [
                    'type' => $plan->type === Plan::TYPE_SME ? 'Small-Medium Enterprise' : 'Enterprise',
                    'limit' => $plan->branch_limit,
                    'count' => $branches,
                ]),
                422
            );
        }

        return [
            'user' => $user,
            'plan' => $plan,
            'branch' => ['branch_id' => $payload['branch_id']],
            'agency' => [],
            'subscription_uuid' => $subscription->uuid,
            'method' => $payload['payment_method'],
            'action' => self::ACTION_RESUBSCRIBE,
            'total_amount' => $subscription->renewalAmount($plan),
            'endDate' => Subscription::termEnd(Carbon::now())->toDateTimeString(),
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
            $action = $meta['action'] ?? self::ACTION_RENEW;

            $changes = match ($action) {
                self::ACTION_RESUBSCRIBE => [
                    'status' => Subscription::STATUS_ACTIVE,
                    'mode' => Subscription::MODE_LIVE,
                    'plan_id' => $paidPlanId,
                    'start_date' => Carbon::now(),
                    'end_date' => $meta['endDate'],
                    'pending_plan_id' => null,
                    'pending_plan_starts_at' => null,
                ],
                self::ACTION_UPGRADE => $subscription->isTest()
                    ? ['plan_id' => $paidPlanId, 'pending_plan_id' => $paidPlanId]
                    : ['plan_id' => $paidPlanId],
                default => !empty($meta['plan_starts_at'])
                    ? [
                        'status' => Subscription::STATUS_ACTIVE,
                        'pending_plan_id' => $paidPlanId,
                        'pending_plan_starts_at' => $meta['plan_starts_at'],
                    ]
                    : [
                        'status' => Subscription::STATUS_ACTIVE,
                        'plan_id' => $paidPlanId,
                        'end_date' => $meta['endDate'],
                    ],
            };

            $subscription->update($changes);

            $payment = $subscription->payments()->create([
                'subscription_id' => $subscription->subscription_id,
                'user_id' => $meta['user']['user_id'] ?? null,
                'plan_id' => $paidPlanId,
                'xendit_invoice_id' => $payload['xendit_invoice_id'] ?? null,
                'payment_reference_id' => $payload['external_id'] ?? null,
                'masked_card_number' => $payload['masked_card_number'] ?? null,
                'price' => $meta['total_amount'],
                'status' => SubscriptionPayment::STATUS_PAID,
                'type' => match ($action) {
                    self::ACTION_RESUBSCRIBE => SubscriptionPayment::TYPE_SUBSCRIPTION,
                    self::ACTION_UPGRADE => SubscriptionPayment::TYPE_UPGRADE,
                    default => SubscriptionPayment::TYPE_RENEWAL,
                },
                'payment_method' => $meta['payment_method'] ?? null,
            ]);

            $subscription->load(['plans', 'pendingPlan']);

            $message = match ($action) {
                self::ACTION_RESUBSCRIBE => __('You are subscribed again.'),
                self::ACTION_UPGRADE => __('Upgraded to :plan.', ['plan' => $subscription->plans?->name]),
                default => !empty($meta['plan_starts_at'])
                    ? __('Renewed. :plan starts on :date.', [
                        'plan' => $subscription->pendingPlan?->name,
                        'date' => Carbon::parse($meta['plan_starts_at'])->toFormattedDateString(),
                    ])
                    : __('Subscription renewed successfully.'),
            };

            return response()->json([
                'status' => true,
                'message' => $message,
                'subscription' => [
                    'uuid' => $subscription->uuid,
                    'status' => $subscription->status,
                    'mode' => $subscription->mode,
                    'start_date' => $subscription->start_date,
                    'end_date' => $subscription->end_date,
                    'renewal' => $subscription->renewalSummary(),
                    'plan' => $subscription->planSummary(),
                    'pending_plan' => $subscription->pendingPlanSummary(),
                    'payment' => $payment->load('plan')->historyRow(),
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

            if (!$subscription->isTest()) {
                throw new Exception(__('A renewal plan change starts when the current period ends.'), 422);
            }

            $plan = $subscription->pendingPlan;
            $today = Carbon::now()->startOfDay();
            $startsAt = Carbon::parse($subscription->pending_plan_starts_at)->startOfDay();

            $forfeited = $startsAt->isAfter($today)
                ? $today->diffInDays($startsAt)
                : 0;

            $subscription->update($subscription->pendingPlanChanges(Carbon::now()));
            $subscription->load('plans');

            return response()->json([
                'status' => true,
                'message' => __(':plan is active now.', ['plan' => $plan?->name]),
                'forfeited_days' => $forfeited,
                'subscription' => [
                    'uuid' => $subscription->uuid,
                    'status' => $subscription->status,
                    'mode' => $subscription->mode,
                    'start_date' => $subscription->start_date,
                    'end_date' => $subscription->end_date,
                    'renewal' => $subscription->renewalSummary(),
                    'plan' => $subscription->planSummary(),
                    'pending_plan' => null,
                ],
            ], 200);
        });
    }

    public function cancelPendingPlan(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $subscription = $this->subscriptionRepository
                ->findLatestForBranch($payload['branch_id']);

            if (!$subscription) {
                throw new Exception(__('This branch has no subscription.'), 404);
            }

            if ($subscription->isTest()) {
                throw new Exception(__('Cancel the subscription during free testing instead.'), 422);
            }

            if (!$subscription->pending_plan_id || $subscription->pendingPlanIsDue()) {
                throw new Exception(__('There is no queued plan change to cancel.'), 422);
            }

            $payment = $subscription->payments()
                ->where('status', SubscriptionPayment::STATUS_PAID)
                ->where('plan_id', $subscription->pending_plan_id)
                ->latest('subscription_payment_id')
                ->lockForUpdate()
                ->first();

            if ($payment) {
                $refunded = XenditService::refundXenditPayment(
                    $payment->xendit_invoice_id,
                    (float) $payment->price,
                    (bool) $payment->masked_card_number
                );

                if (!$refunded) {
                    throw new Exception(__('The plan change could not be cancelled because the refund failed. Please try again.'), 502);
                }

                $payment->update(['status' => SubscriptionPayment::STATUS_REFUNDED]);
            }

            $subscription->update([
                'pending_plan_id' => null,
                'pending_plan_starts_at' => null,
            ]);

            $subscription->load(['plans', 'payments.plan']);

            return response()->json([
                'status' => true,
                'message' => __('Plan change cancelled. Your payment is being refunded.'),
                'subscription' => [
                    'uuid' => $subscription->uuid,
                    'status' => $subscription->status,
                    'mode' => $subscription->mode,
                    'start_date' => $subscription->start_date,
                    'end_date' => $subscription->end_date,
                    'renewal' => $subscription->renewalSummary(),
                    'plan' => $subscription->planSummary(),
                    'pending_plan' => null,
                    'payments' => $subscription->payments->map(fn($payment) => $payment->historyRow())->values(),
                ],
            ], 200);
        });
    }

    public function cancelTest(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $subscription = $this->subscriptionRepository
                ->findLatestForBranch($payload['branch_id']);

            if (!$subscription) {
                throw new Exception(__('This branch has no subscription.'), 404);
            }

            if (!$subscription->canCancelTest()) {
                throw new Exception(__('Only a subscription still in its free testing month can be cancelled.'), 422);
            }

            $payments = $subscription->payments()
                ->where('status', SubscriptionPayment::STATUS_PAID)
                ->lockForUpdate()
                ->get();

            foreach ($payments as $payment) {
                $refunded = XenditService::refundXenditPayment(
                    $payment->xendit_invoice_id,
                    (float) $payment->price,
                    (bool) $payment->masked_card_number
                );

                if (!$refunded) {
                    throw new Exception(__('The subscription could not be cancelled because the refund failed. Please try again.'), 502);
                }

                $payment->update(['status' => SubscriptionPayment::STATUS_REFUNDED]);
            }

            $subscription->update([
                'status' => Subscription::STATUS_CANCELLED,
                'end_date' => Carbon::now(),
                'pending_plan_id' => null,
                'pending_plan_starts_at' => null,
            ]);

            $subscription->load(['plans', 'payments.plan']);

            return response()->json([
                'status' => true,
                'message' => __('Subscription cancelled. Your payment is being refunded.'),
                'subscription' => [
                    'uuid' => $subscription->uuid,
                    'status' => $subscription->status,
                    'mode' => $subscription->mode,
                    'start_date' => $subscription->start_date,
                    'end_date' => $subscription->end_date,
                    'renewal' => $subscription->renewalSummary(),
                    'plan' => $subscription->planSummary(),
                    'pending_plan' => null,
                    'payments' => $subscription->payments->map(fn($payment) => $payment->historyRow())->values(),
                ],
            ], 200);
        });
    }

    public function createSubscription(?User $user, array $payload)
    {
        $planType = $payload['plan_type'] ?? null;

        if (!in_array($planType, Plan::TYPES, true)) {
            throw new \Exception(__('Invalid plan type.'), 422);
        }

        $plan = $this->planRepository->findByCodeAndType($payload['plan_code'] ?? null, $planType);

        if (!$plan) {
            throw new \Exception(__('Plan not found.'), 404);
        }

        $totalAmount = (float) $plan->price;

        $withTrial = !$this->subscriptionRepository->agencyHasUsedTrial(
            !empty($payload['agency_id']) ? (int) $payload['agency_id'] : null
        );

        $testEndsAt = $withTrial
            ? Carbon::now()->addMonths(Subscription::TEST_MONTHS)->toDateString()
            : null;

        $branchDetails = $this->branchDetails($payload);

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
                ...$branchDetails,
                'tin'            => $payload['branch_tin'] ?? null,
                'resubmit_uuid'  => $payload['resubmit_branch_uuid'] ?? null,
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
                'full_address'   => $payload['agency_full_address'] ?? null,
                'latitude'       => $payload['agency_latitude'] ?? null,
                'longitude'      => $payload['agency_longitude'] ?? null,
            ],
            'method' => $payload['payment_method'] ?? null,
            'total_amount' => $totalAmount,
            'endDate' => $testEndsAt ?? Subscription::termEnd(Carbon::now())->toDateString(),
            'test_ends_at' => $testEndsAt,
            'mode' => $withTrial ? Subscription::MODE_TEST : Subscription::MODE_LIVE,
            'type' => 'subscription',
            'status' => true,
            'payment_type' => $payload['payment_type'] ?? null,
        ];
    }

    public function newSubscriber(array $payload)
    {
        $meta = $payload['metadata'];
        $reference_id = $payload['external_id'] ?? null;
        $xendit_invoice_id = $payload['xendit_invoice_id'] ?? null;
        $masked_card_number = $payload['masked_card_number'] ?? null;
        try {

            return DB::transaction(function () use (
                $meta,
                $reference_id,
                $xendit_invoice_id,
                $masked_card_number
            ) {
                if (!empty($meta['branch']['resubmit_uuid'])) {
                    return $this->resubmitIntoNewSubscription(
                        $meta,
                        $reference_id,
                        $xendit_invoice_id,
                        $masked_card_number
                    );
                }

                $plan = $meta['plan'];
                $user = $meta['user'];
                $agency = $meta['agency'];
                $branch = $meta['branch'];
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
                        'full_address' => $agency['full_address'] ?? null,
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
                    'full_address' => $branch['full_address'] ?? null,
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
                    ...Subscription::newTerms(
                        $plan['plan_id'],
                        ($meta['mode'] ?? Subscription::MODE_TEST) === Subscription::MODE_TEST
                    ),
                    'agency_id' => $agencyData->agency_id ?? null,
                ]);

                // 1st brnac
                BranchSubscription::create([
                    'subscription_id' => $subscription->subscription_id,
                    'branch_id' => $branchData->branch_id,
                    'status' => BranchSubscription::STATUS_PENDING,
                ]);

                $subscription->payments()->create([
                    'subscription_id' => $subscription->subscription_id,
                    'user_id' => $meta['user']['user_id'] ?? null,
                    'plan_id' => $plan['plan_id'],
                    'xendit_invoice_id' => $xendit_invoice_id,
                    'payment_reference_id' => $reference_id,
                    'masked_card_number' => $masked_card_number,
                    'price' => $totalAmount,
                    'status' => SubscriptionPayment::STATUS_PAID,
                    'type' => SubscriptionPayment::TYPE_SUBSCRIPTION,
                    'payment_method' => $meta['payment_method'] ?? null,
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
                    'role_name' => RoleEnum::AgencyOwner->value,
                    'branch_id'   => $branchData->branch_id,
                    'employee_id' => $employee->employee_id,
                ]);

                $this->grantOwnerPermissions($employee->employee_id, $branchData->branch_id);

                $this->sendPurchaseMail($user['email'] ?? null, new SubscriptionPurchasedMailer(
                    recipientName: trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'there',
                    planName: $plan['name'] ?? $plan['plan_code'],
                    branchName: $branchData->name,
                    amount: $totalAmount,
                    planType: $plan['type'] ?? null,
                ));

                $this->notifyAdmins(
                    $branchData,
                    $subscription,
                    $user['user_id'],
                    "New subscription request from {$branchData->name} is awaiting your review."
                );

                return response()->json([
                    'status' => true,
                    'message' => __("Almost there! We'll notify you once your branch is verified."),
                    'payment_reference_id' => $reference_id,
                    'branch' => [
                        'branch_id' => $branchData->branch_id,
                        'uuid' => $branchData->uuid,
                        'name' => $branchData->name,
                        'description' => $branchData->description,
                        'image' => $branchData->image,
                        'status' => $branchData->status,
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

            $this->refundFailedPayment($xendit_invoice_id, $meta['total_amount'], 'Subscription creation failed.');

            return response()->json([
                'status' => false,
                'message' => 'Subscription failed. If your payment was made, it will be automatically refunded.',
            ], 500);
        }
    }



    private function branchDetails(array $payload): array
    {
        $image = ($payload['branch_image'] ?? null) instanceof UploadedFile
            ? SupabaseService::store($payload['branch_image'])
            : null;

        $document = ($payload['branch_document'] ?? null) instanceof UploadedFile
            ? SupabaseService::store($payload['branch_document'])
            : null;

        return [
            'name'           => $payload['branch_name'] ?? null,
            'street'         => $payload['branch_street'] ?? null,
            'description'    => $payload['branch_description'] ?? null,
            'city'           => $payload['branch_city'] ?? null,
            'province'       => $payload['branch_province'] ?? null,
            'country'        => $payload['branch_country'] ?? null,
            'full_address'   => $payload['branch_full_address'] ?? null,
            'email'          => $payload['branch_email'] ?? null,
            'contact_number' => $payload['branch_contact_number'] ?? null,
            'image'          => is_array($image) ? ($image['url'] ?? null) : null,
            'document'       => is_array($document) ? ($document['url'] ?? null) : null,
            'setting'        => $payload['branch_settings'] ?? null,
            'latitude'       => $payload['branch_latitude'] ?? null,
            'longitude'      => $payload['branch_longitude'] ?? null,
        ];
    }

    private function storeBranch(Agency $agency, Subscription $subscription, array $branch, string $type): array
    {
        $latitude = $branch['latitude'] ?? null;
        $longitude = $branch['longitude'] ?? null;

        if (empty($latitude) || empty($longitude)) {
            $geo = $this->nominatimService->geocodeAddress([
                'street' => $branch['street'] ?? null,
                'city' => $branch['city'] ?? null,
                'province' => $branch['province'] ?? null,
                'country' => $branch['country'] ?? null,
            ]);

            $latitude = $geo['lat'] ?? null;
            $longitude = $geo['lng'] ?? null;
        }

        $location = $this->locationRepository->create([
            'street' => $branch['street'] ?? null,
            'city' => $branch['city'] ?? null,
            'province' => $branch['province'] ?? null,
            'country' => $branch['country'] ?? null,
            'full_address' => $branch['full_address'] ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        $branchData = $this->branchRepository->create([
            'agency_id' => $agency->agency_id,
            'location_id' => $location->location_id,
            'description' => $branch['description'] ?? null,
            'name' => $branch['name'] ?? null,
            'contact_number' => $branch['contact_number'] ?? null,
            'image' => $branch['image'] ?? null,
            'document' => $branch['document'] ?? null,
            'settings' => $branch['setting'] ?? null,
            'email' => $branch['email'],
        ]);

        BranchSubscription::create([
            'subscription_id' => $subscription->subscription_id,
            'branch_id' => $branchData->branch_id,
            'status' => BranchSubscription::STATUS_PENDING,
            'type' => $type,
        ]);

        $owner = User::find($agency->registered_by);
        $ownerEmployee = $owner
            ? $this->employeeRepository->findEmployeeByFields([
                ['user_id', '=', $owner->user_id],
            ])
            : null;

        if ($ownerEmployee) {
            $ownerEmployee->employeeBranch()->create([
                'role_name' => RoleEnum::AgencyOwner->value,
                'branch_id' => $branchData->branch_id,
                'employee_id' => $ownerEmployee->employee_id,
            ]);

            $this->grantOwnerPermissions($ownerEmployee->employee_id, $branchData->branch_id);
        }

        return [$branchData, $location];
    }

    private function branchCreatedResponse(Branch $branch, $location, Agency $agency, string $message, array $extra = [])
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            ...$extra,
            'branch' => [
                'branch_id' => $branch->branch_id,
                'uuid' => $branch->uuid,
                'name' => $branch->name,
                'description' => $branch->description,
                'image' => $branch->image,
                'status' => $branch->status,
                'contact_number' => $branch->contact_number,
                'email' => $branch->email,
                'tin' => data_get($branch->settings, 'tin'),
                'location' => [
                    'street' => $location->street,
                    'city' => $location->city,
                    'province' => $location->province,
                    'country' => $location->country,
                    'full_address' => $location->full_address,
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

            [$branchData, $branchLocation] = $this->storeBranch(
                $agency,
                $subscription,
                $this->branchDetails($payload),
                BranchSubscription::TYPE_INCLUDED
            );

            $this->notifyAdmins(
                $branchData,
                $subscription,
                $user->user_id,
                "New branch request from {$branchData->name} (included in an existing subscription) is awaiting your review."
            );

            return $this->branchCreatedResponse($branchData, $branchLocation, $agency, __('Branch added and sent for review.'));
        });
    }

    public function makeAdditionalBranch(array $payload, User $user)
    {
        AuthGuard::requireUser($user);
        set_time_limit(self::PAYMENT_TIME_LIMIT);
        $paymentMethod = PaymentFactory::make($payload['payment_method']);
        $detail = $this->createAdditionalBranch($user, $payload);

        return $paymentMethod->subscriptionInvoice($payload, $detail);
    }

    public function createAdditionalBranch(User $user, array $payload): array
    {
        if (empty($payload['agency_id']) || empty($payload['subscription_uuid'])) {
            throw new Exception(__('A subscription is required to add an additional branch.'), 422);
        }

        $this->ensureNotTesting($payload['agency_id']);

        $subscription = $this->subscriptionRepository->findForAgency(
            $payload['agency_id'],
            $payload['subscription_uuid']
        );

        if (!$subscription) {
            throw new Exception(__('Subscription not found.'), 404);
        }

        if ($subscription->hasOpenSlot()) {
            throw new Exception(__('This subscription still has a free branch slot. Use it instead.'), 409);
        }

        if (!$subscription->canAddAdditionalBranch()) {
            throw new Exception(__('This subscription can\'t take an additional branch.'), 409);
        }

        $quote = $subscription->additionalBranchQuote();

        if ($quote['amount'] <= 0) {
            throw new Exception(__('Additional branches are not available on this plan.'), 422);
        }

        return [
            'user' => $user,
            'plan' => $subscription->plans,
            'branch' => $this->branchDetails($payload),
            'agency' => ['id' => $subscription->agency_id],
            'subscription_uuid' => $subscription->uuid,
            'method' => $payload['payment_method'] ?? null,
            'action' => self::ACTION_ADDITIONAL_BRANCH,
            'type' => self::PAYMENT_TYPE_ADDITIONAL_BRANCH,
            'total_amount' => $quote['amount'],
            'endDate' => $subscription->end_date->toDateString(),
            'status' => true,
            'payment_type' => 'SUBSCRIPTION',
        ];
    }

    public function settlePayment(array $result)
    {
        return match ($result['metadata']['type'] ?? null) {
            'renewal' => $this->renewSubscriber($result),
            self::PAYMENT_TYPE_ADDITIONAL_BRANCH => $this->addBranchSubscriber($result),
            default => $this->newSubscriber($result),
        };
    }

    public function addBranchSubscriber(array $payload)
    {
        $meta = $payload['metadata'];

        try {
            return DB::transaction(function () use ($payload, $meta) {
                $agency = Agency::where('agency_id', $meta['agency']['id'] ?? null)
                    ->lockForUpdate()
                    ->first();

                $subscription = $agency
                    ? $this->subscriptionRepository->findForAgency($agency->agency_id, $meta['subscription_uuid'] ?? '', true)
                    : null;

                if (
                    !$subscription
                    || !in_array($subscription->status, [Subscription::STATUS_PENDING, Subscription::STATUS_ACTIVE], true)
                ) {
                    throw new Exception(__('Subscription not found.'), 404);
                }

                [$branch, $location] = $this->storeBranch(
                    $agency,
                    $subscription,
                    $meta['branch'],
                    BranchSubscription::TYPE_ADDITIONAL
                );

                $subscription->payments()->create([
                    'subscription_id' => $subscription->subscription_id,
                    'user_id' => $meta['user']['user_id'] ?? null,
                    'plan_id' => $subscription->plan_id,
                    'branch_id' => $branch->branch_id,
                    'xendit_invoice_id' => $payload['xendit_invoice_id'] ?? null,
                    'payment_reference_id' => $payload['external_id'] ?? null,
                    'masked_card_number' => $payload['masked_card_number'] ?? null,
                    'price' => $meta['total_amount'],
                    'status' => SubscriptionPayment::STATUS_PAID,
                    'type' => SubscriptionPayment::TYPE_ADDITIONAL_BRANCH,
                    'payment_method' => $meta['payment_method'] ?? null,
                ]);

                $this->notifyAdmins(
                    $branch,
                    $subscription,
                    $meta['user']['user_id'],
                    "New additional branch request from {$branch->name} is awaiting your review."
                );

                return $this->branchCreatedResponse(
                    $branch,
                    $location,
                    $agency,
                    __('Payment received. Branch sent for review.'),
                    ['payment_reference_id' => $payload['external_id'] ?? null]
                );
            });
        } catch (\Exception $e) {
            Log::error('Additional branch creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $this->refundFailedPayment($payload['xendit_invoice_id'] ?? null, $meta['total_amount'], 'Additional branch creation failed.');

            return response()->json([
                'status' => false,
                'message' => 'Adding the branch failed. If your payment was made, it will be automatically refunded.',
            ], 500);
        }
    }

    private function refundFailedPayment(?string $invoiceId, mixed $amount, string $message): void
    {
        Http::withOptions([
            'verify' => false,
        ])->withBasicAuth($this->secretKey, '')
            ->post('https://api.xendit.co/refunds', [
                'invoice_id'   => $invoiceId,
                'reference_id' => (string) Str::uuid(),
                'amount'       => $amount,
                'reason'       => 'CANCELLATION',
                'metadata' => [
                    'message' => $message,
                ],
            ]);
    }

    public function resubmitBranch(array $payload, User $user)
    {
        $branch = $this->resolveResubmitBranch($payload);

        $image = ($payload['branch_image'] ?? null) instanceof UploadedFile
            ? SupabaseService::store($payload['branch_image'])
            : null;

        $document = ($payload['branch_document'] ?? null) instanceof UploadedFile
            ? SupabaseService::store($payload['branch_document'])
            : null;

        $agencyFields = !empty($payload['agency_name'])
            ? [
                'name' => $payload['agency_name'],
                'email' => $payload['agency_email'] ?? null,
                'description' => $payload['agency_description'] ?? null,
                'street' => $payload['agency_street'] ?? null,
                'city' => $payload['agency_city'] ?? null,
                'province' => $payload['agency_province'] ?? null,
                'country' => $payload['agency_country'] ?? null,
                'full_address' => $payload['agency_full_address'] ?? null,
                'latitude' => $payload['agency_latitude'] ?? null,
                'longitude' => $payload['agency_longitude'] ?? null,
                ...collect(['image', 'id_front', 'id_back', 'document'])
                    ->mapWithKeys(fn($key) => [
                        $key => ($payload["agency_{$key}"] ?? null) instanceof UploadedFile
                            ? (SupabaseService::store($payload["agency_{$key}"])['url'] ?? null)
                            : null,
                    ])
                    ->all(),
            ]
            : null;

        return DB::transaction(function () use ($payload, $user, $branch, $image, $document, $agencyFields) {
            $link = $this->lockRejectedLink($branch);
            $subscription = $link->subscription;

            if (!$this->subscriptionHasRoomFor($subscription, $branch)) {
                throw new Exception(
                    'This branch\'s subscription has no free slot. Purchase a new subscription to resubmit it.',
                    409
                );
            }

            if ($agencyFields) {
                $this->applyAgencyResubmission($branch, $agencyFields);
            }

            $this->applyResubmission($branch, [
                'name' => $payload['branch_name'],
                'email' => $payload['branch_email'],
                'contact_number' => $payload['branch_contact_number'],
                'description' => $payload['branch_description'],
                'tin' => $payload['branch_tin'] ?? null,
                'street' => $payload['branch_street'],
                'city' => $payload['branch_city'],
                'province' => $payload['branch_province'],
                'country' => $payload['branch_country'],
                'full_address' => $payload['branch_full_address'] ?? null,
                'latitude' => $payload['branch_latitude'] ?? null,
                'longitude' => $payload['branch_longitude'] ?? null,
                'image' => is_array($image) ? ($image['url'] ?? null) : null,
                'document' => is_array($document) ? ($document['url'] ?? null) : null,
            ]);

            $link->update([
                'status' => BranchSubscription::STATUS_PENDING,
                'type' => BranchSubscription::TYPE_INCLUDED,
            ]);

            $this->notifyAdmins(
                $branch,
                $subscription,
                $user->user_id,
                "{$branch->name} was resubmitted for review after being rejected."
            );

            return $this->resubmissionResponse($branch, $subscription->load('plans'), __('Branch resubmitted for review.'));
        });
    }

    public function makeResubmitPurchase(array $payload, User $user)
    {
        AuthGuard::requireUser($user);
        set_time_limit(self::PAYMENT_TIME_LIMIT);

        $branch = $this->resolveResubmitBranch($payload);
        $link = BranchSubscription::where('branch_id', $branch->branch_id)
            ->latest('branch_subscription_id')
            ->first();

        if (!$link || $link->status !== BranchSubscription::STATUS_REJECTED) {
            throw new Exception('Only rejected branches can be resubmitted.', 422);
        }

        $payload['resubmit_branch_uuid'] = $branch->uuid;

        $paymentMethod = PaymentFactory::make($payload['payment_method']);
        $detail = $this->createSubscription($user, $payload);

        return $paymentMethod->subscriptionInvoice($payload, $detail);
    }

    private function resubmitIntoNewSubscription(array $meta, ?string $reference, ?string $invoiceId, ?string $maskedCard)
    {
        $branchMeta = $meta['branch'];
        $plan = $meta['plan'];
        $user = $meta['user'];

        $branch = Branch::with('location')->where('uuid', $branchMeta['resubmit_uuid'])->first();

        if (!$branch || (int) $branch->agency_id !== (int) ($meta['agency']['id'] ?? 0)) {
            throw new Exception('Branch not found.', 404);
        }

        $link = $this->lockRejectedLink($branch);
        $refunded = $link->subscription?->status === Subscription::STATUS_REJECTED;

        $terms = Subscription::newTerms(
            $plan['plan_id'],
            ($meta['mode'] ?? Subscription::MODE_TEST) === Subscription::MODE_TEST
        );

        if ($refunded) {
            $subscription = $link->subscription;
            $subscription->update([
                ...$terms,
                'status' => Subscription::STATUS_PENDING,
            ]);
        } else {
            $subscription = $this->subscriptionRepository->create([
                ...$terms,
                'agency_id' => $branch->agency_id,
            ]);
        }

        $subscription->payments()->create([
            'subscription_id' => $subscription->subscription_id,
            'user_id' => $meta['user']['user_id'] ?? null,
            'plan_id' => $plan['plan_id'],
            'xendit_invoice_id' => $invoiceId,
            'payment_reference_id' => $reference,
            'masked_card_number' => $maskedCard,
            'price' => (float) $meta['total_amount'],
            'status' => SubscriptionPayment::STATUS_PAID,
            'type' => SubscriptionPayment::TYPE_SUBSCRIPTION,
            'payment_method' => $meta['payment_method'] ?? null,
        ]);

        if (!empty($meta['agency']['name'])) {
            $this->applyAgencyResubmission($branch, $meta['agency']);
        }

        $this->applyResubmission($branch, $branchMeta);

        $link->update([
            'subscription_id' => $subscription->subscription_id,
            'status' => BranchSubscription::STATUS_PENDING,
            'type' => BranchSubscription::TYPE_INCLUDED,
        ]);

        $this->notifyAdmins(
            $branch,
            $subscription,
            $user['user_id'],
            $refunded
                ? "{$branch->name} paid again and was resubmitted for review."
                : "{$branch->name} was resubmitted for review with a new {$plan['name']} subscription."
        );

        $this->sendPurchaseMail($user['email'] ?? null, new SubscriptionPurchasedMailer(
            recipientName: trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'there',
            planName: $plan['name'] ?? $plan['plan_code'],
            branchName: $branch->name,
            amount: (float) $meta['total_amount'],
            planType: $plan['type'] ?? null,
        ));

        return $this->resubmissionResponse(
            $branch,
            $subscription->load('plans'),
            __('Payment received. Branch resubmitted for review.')
        );
    }

    private function applyAgencyResubmission(Branch $branch, array $fields): void
    {
        $agency = $branch->agencies()->with('locations')->first();

        if (!$agency || $agency->status === Agency::STATUS_VERIFIED) {
            return;
        }

        $latitude = $fields['latitude'] ?? null;
        $longitude = $fields['longitude'] ?? null;

        if (empty($latitude) || empty($longitude)) {
            $geo = $this->nominatimService->geocodeAddress([
                'street' => $fields['street'] ?? null,
                'city' => $fields['city'] ?? null,
                'province' => $fields['province'] ?? null,
                'country' => $fields['country'] ?? null,
            ]);

            $latitude = $geo['lat'] ?? null;
            $longitude = $geo['lng'] ?? null;
        }

        $location = [
            'street' => $fields['street'] ?? null,
            'city' => $fields['city'] ?? null,
            'province' => $fields['province'] ?? null,
            'country' => $fields['country'] ?? null,
            'full_address' => $fields['full_address'] ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];

        if ($agency->locations) {
            $agency->locations->update($location);
        } else {
            $agency->location_id = $this->locationRepository->create($location)->location_id;
        }

        $agency->update([
            'name' => $fields['name'],
            'email' => $fields['email'] ?? $agency->email,
            'description' => $fields['description'] ?? $agency->description,
            'image' => ($fields['image'] ?? null) ?: $agency->image,
            'id_front' => ($fields['id_front'] ?? null) ?: $agency->id_front,
            'id_back' => ($fields['id_back'] ?? null) ?: $agency->id_back,
            'document' => ($fields['document'] ?? null) ?: $agency->document,
            'status' => Agency::STATUS_PENDING,
        ]);
    }

    private function resolveResubmitBranch(array $payload): Branch
    {
        $branch = Branch::with('location')
            ->where('uuid', $payload['target_branch_uuid'])
            ->first();

        if (!$branch || (int) $branch->agency_id !== (int) $payload['agency_id']) {
            throw new Exception('Branch not found.', 404);
        }

        return $branch;
    }

    private function lockRejectedLink(Branch $branch): BranchSubscription
    {
        $link = BranchSubscription::with('subscription')
            ->where('branch_id', $branch->branch_id)
            ->latest('branch_subscription_id')
            ->lockForUpdate()
            ->first();

        if (!$link || $link->status !== BranchSubscription::STATUS_REJECTED) {
            throw new Exception('Only rejected branches can be resubmitted.', 422);
        }

        return $link;
    }

    private function subscriptionHasRoomFor(?Subscription $subscription, Branch $branch): bool
    {
        if (!$subscription || in_array($subscription->status, [Subscription::STATUS_REJECTED, Subscription::STATUS_EXPIRED], true)) {
            return false;
        }

        return (bool) $this->subscriptionRepository->findSubscriptionWithRoom($branch->agency_id, $subscription->uuid);
    }

    private function applyResubmission(Branch $branch, array $fields): void
    {
        $latitude = $fields['latitude'] ?? null;
        $longitude = $fields['longitude'] ?? null;

        if (empty($latitude) || empty($longitude)) {
            $geo = $this->nominatimService->geocodeAddress([
                'street' => $fields['street'] ?? null,
                'city' => $fields['city'] ?? null,
                'province' => $fields['province'] ?? null,
                'country' => $fields['country'] ?? null,
            ]);

            $latitude = $geo['lat'] ?? null;
            $longitude = $geo['lng'] ?? null;
        }

        $location = [
            'street' => $fields['street'] ?? null,
            'city' => $fields['city'] ?? null,
            'province' => $fields['province'] ?? null,
            'country' => $fields['country'] ?? null,
            'full_address' => $fields['full_address'] ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];

        if ($branch->location) {
            $branch->location->update($location);
        } else {
            $branch->location_id = $this->locationRepository->create($location)->location_id;
        }

        $branch->update([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'contact_number' => $fields['contact_number'],
            'description' => $fields['description'],
            'image' => ($fields['image'] ?? null) ?: $branch->image,
            'document' => ($fields['document'] ?? null) ?: $branch->document,
            'settings' => array_merge($branch->settings ?? [], ['tin' => $fields['tin'] ?? null]),
            'status' => Branch::STATUS_PENDING,
        ]);
    }

    private function resubmissionResponse(Branch $branch, Subscription $subscription, string $message)
    {
        $branch->load(['location', 'agencies.locations']);

        return response()->json([
            'status' => true,
            'message' => $message,
            'agency' => $branch->agencies ? [
                'agency_id' => $branch->agencies->agency_id,
                'name' => $branch->agencies->name,
                'email' => $branch->agencies->email,
                'description' => $branch->agencies->description,
                'image' => $branch->agencies->image,
                'id_front' => $branch->agencies->id_front,
                'id_back' => $branch->agencies->id_back,
                'document' => $branch->agencies->document,
                'status' => $branch->agencies->status,
                'location' => $branch->agencies->locations,
            ] : null,
            'subscription_uuid' => $subscription->uuid,
            'subscription' => [
                'uuid' => $subscription->uuid,
                'plan_name' => $subscription->plans?->name,
                'plan_code' => $subscription->plans?->plan_code,
                'plan_type' => $subscription->plans?->type,
                'status' => $subscription->status,
                'end_date' => $subscription->end_date,
            ],
            'branch' => [
                'branch_id' => $branch->branch_id,
                'uuid' => $branch->uuid,
                'name' => $branch->name,
                'description' => $branch->description,
                'image' => $branch->image,
                'document' => $branch->document,
                'status' => $branch->status,
                'review_status' => $branch->status,
                'rejection_reason' => null,
                'contact_number' => $branch->contact_number,
                'email' => $branch->email,
                'settings' => $branch->settings,
                'location' => [
                    'street' => $branch->location?->street,
                    'city' => $branch->location?->city,
                    'province' => $branch->location?->province,
                    'country' => $branch->location?->country,
                    'full_address' => $branch->location?->full_address,
                    'latitude' => $branch->location?->latitude,
                    'longitude' => $branch->location?->longitude,
                ],
            ],
        ]);
    }

    private function notifyAdmins(Branch $branch, Subscription $subscription, int $fromUserId, string $message): void
    {
        $admins = User::whereIn('user_id', PlatformAdmin::pluck('user_id'))
            ->get(['user_id', 'uuid']);

        foreach ($admins as $admin) {
            $this->notificationRepository->create([
                'branch_id' => $branch->branch_id,
                'to_user_id' => $admin->user_id,
                'from_user_id' => $fromUserId,
                'message_type' => 'Subscription',
                'message' => $message,
            ]);

            try {
                event(new NotificationEvent(
                    $admin->uuid,
                    $branch->uuid,
                    $message,
                    (string) $subscription->subscription_id,
                    'Subscription',
                    $subscription
                ));
            } catch (\Throwable $e) {
                Log::warning('Admin subscription broadcast failed', [
                    'subscription_id' => $subscription->subscription_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendPurchaseMail(?string $email, SubscriptionPurchasedMailer $mail): void
    {
        if (empty($email)) {
            return;
        }

        try {
            Mail::to($email)->send($mail);
        } catch (\Throwable $e) {
            Log::warning('Subscription purchase email failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
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

            $this->verificationLogRepository->create([
                'branch_subscription_id' => $link->branch_subscription_id,
                'action' => VerificationLog::ACTION_APPROVED,
                'scope' => $agency && $agency->status !== Agency::STATUS_VERIFIED
                    ? VerificationLog::SCOPE_BOTH
                    : VerificationLog::SCOPE_BRANCH,
                'action_by' => Auth::id(),
            ]);

            $link->update(['status' => BranchSubscription::STATUS_APPROVED]);

            if ($branch->status !== Branch::STATUS_VERIFIED) {
                $branch->update(['status' => Branch::STATUS_VERIFIED]);
            }

            if ($agency && $agency->status !== Agency::STATUS_VERIFIED) {
                $agency->update(['status' => Agency::STATUS_VERIFIED]);
            }


            if ($subscription && $subscription->status === Subscription::STATUS_PENDING) {
                $startDate = Carbon::now();

                $subscription->update($subscription->isTest() && $subscription->pending_plan_id
                    ? [
                        ...Subscription::testTerms($subscription->pending_plan_id, $startDate),
                        'status' => Subscription::STATUS_ACTIVE,
                    ]
                    : [
                        'status' => Subscription::STATUS_ACTIVE,
                        'start_date' => $startDate,
                        'end_date' => Subscription::termEnd($startDate),
                    ]);
            }

            DB::afterCommit(function () use ($branch) {
                try {
                    $this->announceApproval($branch);
                } catch (\Throwable $e) {
                    Log::error('Branch approval notice failed', [
                        'branch_id' => $branch->branch_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

            return response()->json([
                'message' => '',
                'data' => $link->fresh(['branch.agencies', 'subscription.plans']),
            ]);
        });
    }

    private function announceApproval(Branch $branch): void
    {
        $owner = $branch->agencies?->registered_by
            ? User::find($branch->agencies->registered_by)
            : null;

        if (!$owner) {
            return;
        }

        $message = "{$branch->name} was approved and is now active.";

        $this->notificationRepository->create([
            'branch_id' => $branch->branch_id,
            'to_user_id' => $owner->user_id,
            'from_user_id' => Auth::id(),
            'message_type' => 'Subscription',
            'message' => $message,
        ]);

        event(new NotificationEvent(
            (string) $owner->uuid,
            (string) $branch->uuid,
            $message,
            (string) $branch->branch_id,
            'Subscription',
            null
        ));
    }

    public function paymentInvoice(string $reference, ?int $agencyId = null): array
    {
        $payment = $this->subscriptionRepository->findPaymentByReference($reference);

        if (!$payment || ($agencyId !== null && (int) $payment->subscription?->agency_id !== $agencyId)) {
            throw new Exception('Payment not found.', 404);
        }

        $details = [
            'plan' => $payment->plan?->name,
            'plan_type' => $payment->plan?->type,
            'type' => $payment->type,
        ];

        $invoice = XenditService::invoice($payment->xendit_invoice_id);

        if ($invoice) {
            return [
                'receipt' => [
                    ...$details,
                    'method' => 'GCASH',
                    'xendit_id' => $invoice['id'] ?? $payment->xendit_invoice_id,
                    'status' => $invoice['status'] ?? null,
                    'amount' => (float) ($invoice['paid_amount'] ?? $invoice['amount'] ?? $payment->price),
                    'currency' => $invoice['currency'] ?? 'PHP',
                    'reference_id' => $invoice['external_id'] ?? $payment->payment_reference_id,
                    'paid_at' => $invoice['paid_at'] ?? $payment->created_at?->toIso8601String(),
                ],
            ];
        }

        $charge = XenditService::cardCharge($payment->xendit_invoice_id);

        if (!$charge) {
            throw new Exception('Xendit has no invoice for this payment.', 404);
        }

        return [
            'receipt' => [
                ...$details,
                'method' => 'CREDIT-CARD',
                'xendit_id' => $charge['id'] ?? $payment->xendit_invoice_id,
                'status' => $charge['status'] ?? null,
                'amount' => (float) ($charge['capture_amount'] ?? $charge['authorized_amount'] ?? $payment->price),
                'currency' => $charge['currency'] ?? 'PHP',
                'card_brand' => $charge['card_brand'] ?? null,
                'card_type' => $charge['card_type'] ?? null,
                'masked_card_number' => $charge['masked_card_number'] ?? $payment->masked_card_number,
                'reference_id' => $charge['external_id'] ?? $payment->payment_reference_id,
                'paid_at' => $charge['created'] ?? $payment->created_at?->toIso8601String(),
            ],
        ];
    }

    public function verificationLogs(array $payload)
    {
        $link = $this->resolveBranchLink($payload);

        $logs = ($payload['for'] ?? null) === 'agency'
            ? $this->verificationLogRepository->forAgency($link->branch->agency_id)
            : $this->verificationLogRepository->forBranch($link->branch_id);

        $logs = $logs
            ->map(fn(VerificationLog $log) => [
                'branch_uuid' => $log->branchSubscription?->branch?->uuid,
                'branch_name' => $log->branchSubscription?->branch?->name,
                'action' => $log->action,
                'scope' => $log->scope,
                'reason' => $log->reason,
                'reviewed_by' => trim(
                    ($log->actor?->systemOwner?->first_name ?? '') . ' '
                        . ($log->actor?->systemOwner?->last_name ?? '')
                ) ?: null,
                'created_at' => $log->created_at,
            ])
            ->values();

        return response()->json(['data' => $logs]);
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
        $link = DB::transaction(function () use ($payload) {

            $link = $this->resolveBranchLink($payload);

            if ($link->status !== BranchSubscription::STATUS_PENDING) {
                throw new Exception("Only pending branches can be rejected (current status: {$link->status}).", 404);
            }

            $link->load(['branch.agencies', 'subscription.payments']);

            $subscription = $link->subscription;
            $agency = $link->branch?->agencies;
            $reason = trim((string) ($payload['rejection_reason'] ?? ''));

            $agencyUnverified = $agency && $agency->status !== Agency::STATUS_VERIFIED;

            $isAdditional = $link->type === BranchSubscription::TYPE_ADDITIONAL;

            $refunds = !$isAdditional && BranchSubscription::where('subscription_id', $link->subscription_id)
                ->where('branch_subscription_id', '!=', $link->branch_subscription_id)
                ->where('status', '!=', BranchSubscription::STATUS_REJECTED)
                ->doesntExist();

            $rejectsAgency = $agencyUnverified && (
                $refunds || in_array($payload['rejection_scope'] ?? null, [
                    VerificationLog::SCOPE_AGENCY,
                    VerificationLog::SCOPE_BOTH,
                ], true)
            );

            $scope = $rejectsAgency ? VerificationLog::SCOPE_BOTH : VerificationLog::SCOPE_BRANCH;

            $link->update(['status' => BranchSubscription::STATUS_REJECTED]);

            $this->verificationLogRepository->create([
                'branch_subscription_id' => $link->branch_subscription_id,
                'action' => VerificationLog::ACTION_REJECTED,
                'scope' => $scope,
                'reason' => $reason ?: null,
                'action_by' => Auth::id(),
            ]);

            if ($scope !== VerificationLog::SCOPE_BRANCH) {
                $agency->update(['status' => Agency::STATUS_REJECTED]);
            }

            if ($link->branch && $link->branch->status === Branch::STATUS_PENDING) {
                $link->branch->update(['status' => Branch::STATUS_REJECTED]);
            }

            if ($isAdditional && $subscription) {
                $payment = $subscription->payments
                    ->where('status', SubscriptionPayment::STATUS_PAID)
                    ->where('type', SubscriptionPayment::TYPE_ADDITIONAL_BRANCH)
                    ->where('branch_id', $link->branch_id)
                    ->first();

                if ($payment) {
                    $refunded = XenditService::refundXenditPayment(
                        $payment->xendit_invoice_id,
                        (float) $payment->price,
                        (bool) $payment->masked_card_number
                    );

                    if (!$refunded) {
                        throw new Exception('Branch cannot be rejected because the payment refund failed.');
                    }

                    $payment->update([
                        'status' => SubscriptionPayment::STATUS_REFUNDED,
                    ]);
                }
            } elseif ($refunds && $subscription) {
                $payment = $subscription->payments
                    ->where('status', SubscriptionPayment::STATUS_PAID)
                    ->sortByDesc('created_at')
                    ->first();

                if ($payment) {
                    $refunded = XenditService::refundXenditPayment(
                        $payment->xendit_invoice_id,
                        (float) $payment->price,
                        (bool) $payment->masked_card_number
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

            return $link;
        });

        try {
            $this->announceRejection($link->branch, (string) $link->fresh()->rejection_reason);
        } catch (\Throwable $e) {
            Log::error('Branch rejection notice failed', [
                'branch_subscription_id' => $link->branch_subscription_id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Branch request rejected.',
            'data' => $link->fresh(['branch.agencies', 'subscription.plans']),
        ]);
    }

    private function announceRejection(?Branch $branch, string $reason): void
    {
        if (!$branch) {
            return;
        }

        $agency = $branch->agencies;
        $owner = $agency?->registered_by
            ? User::find($agency->registered_by)
            : null;

        $recipientName = trim(
            ($owner?->client?->first_name ?? '')
                . ' ' . ($owner?->client?->last_name ?? '')
        ) ?: ($agency?->name ?? 'there');

        $email = $branch->email ?: ($agency?->email ?: $owner?->email);

        if ($email) {
            Mail::to($email)->send(new BranchRejectedMailer(
                recipientName: $recipientName,
                branchName: $branch->name,
                agencyName: $agency?->name ?? 'your agency',
                reason: $reason ?: 'No reason was provided.',
            ));
        }

        if (!$owner) {
            return;
        }

        $message = "{$branch->name} was not approved."
            . ($reason ? " Reason: {$reason}" : '');

        $this->notificationRepository->create([
            'branch_id' => $branch->branch_id,
            'to_user_id' => $owner->user_id,
            'from_user_id' => Auth::id(),
            'message_type' => 'Subscription',
            'message' => $message,
        ]);

        event(new NotificationEvent(
            (string) $owner->uuid,
            (string) $branch->uuid,
            $message,
            (string) $branch->branch_id,
            'Subscription',
            null
        ));
    }
}
