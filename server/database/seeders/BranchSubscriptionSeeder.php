<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Agency;
use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\EmployeePermission;
use App\Models\Location;
use App\Models\Module;
use App\Models\Plan;
use App\Models\PlatformAdmin;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Models\VerificationLog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BranchSubscriptionSeeder extends Seeder
{
    private const SUBSCRIPTIONS = [
        ['plan' => 'C', 'type' => Plan::TYPE_ENTERPRISE, 'branches' => 10, 'status' => 'active'],
        ['plan' => 'B', 'type' => Plan::TYPE_SME, 'branches' => 2, 'status' => 'active'],
        ['plan' => 'A', 'type' => Plan::TYPE_SME, 'branches' => 2, 'status' => 'active'],
        ['plan' => 'C', 'type' => Plan::TYPE_SME, 'branches' => 1, 'status' => 'active'],
    ];

    private const OWNERS = [
        ['Prince2', 'Sestoso', 'AMUMA Incorporation'],
        ['Marissa', 'Villanueva', 'Sunrise Elder Care'],
        ['Rodrigo', 'Dela Pena', 'Golden Years Care Group'],
        ['Lorenzo', 'Dimaculangan', 'CareStream Home Health Service Inc.'],
    ];

    private const AMUMA_EXTRA_BRANCHES = [
        ['AMUMA Incorporation - Panabo Branch', 'Rizal Street', 'Panabo City', 'Davao del Norte', 7.2995764, 125.6814238],
        ['AMUMA Incorporation - Samal Branch', 'Purok 2, Circumferential Road', 'Samal', 'Davao del Norte', 7.0560898, 125.7211018],
        ['AMUMA Incorporation - Tagum Branch', 'Roxas Avenue', 'Tagum City', 'Davao del Norte', 7.4470784, 125.8094853],
        ['AMUMA Incorporation - Digos Branch', 'Bonifacio Street', 'Digos City', 'Davao del Sur', 6.7422588, 125.3662853],
        ['AMUMA Incorporation - Mati Branch', 'Rizal Street', 'Mati City', 'Davao Oriental', 6.9549000, 126.2187000],
        ['AMUMA Incorporation - Toril Branch', 'Agton Street', 'Davao City', 'Davao del Sur', 7.0189000, 125.4950000],
        ['AMUMA Incorporation - Buhangin Branch', 'Cabaguio Avenue', 'Davao City', 'Davao del Sur', 7.1180000, 125.6130000],
        ['AMUMA Incorporation - Matina Branch', 'MacArthur Highway', 'Davao City', 'Davao del Sur', 7.0600000, 125.5900000],
        ['AMUMA Incorporation - Calinan Branch', 'Calinan Poblacion Road', 'Davao City', 'Davao del Sur', 7.1917000, 125.4508000],
    ];

    private const BRANCHES = [
        ['AMUMA Incorporation - Davao Branch', 'Rizal Street', 'Davao City', 'Davao del Sur', 7.0760930, 125.6015037],
        ['Sunrise Elder Care - Butuan Branch', 'Quezon Avenue', 'Butuan City', 'Agusan del Norte', 8.9477147, 125.5432054],
        ['Sunrise Elder Care - Digos Branch', 'Bonifacio Street', 'Digos City', 'Davao del Sur', 6.7422588, 125.3662853],
        ['Golden Years Care Group - Cebu Branch', 'Osmena Boulevard', 'Cebu City', 'Cebu', 10.3081889, 123.8936359],
        ['Golden Years Care Group - Mandaue Branch', 'Magsaysay Street', 'Mandaue City', 'Cebu', 10.3269049, 123.9427295],
        ['CareStream Home Health Service Inc. - Cagayan de Oro Branch', 'Bonifacio Street', 'Cagayan de Oro', 'Misamis Oriental', 8.4756417, 124.6421532],
    ];

    private const AGENCY_REJECTION_REASONS = [
        'The uploaded ID is blurry or cropped. Please re-submit a clear photo of the front and back.',
        'The uploaded ID is already expired. Please re-submit a valid, unexpired government ID.',
        'The business document is expired or not a valid registration. Please re-submit a current DTI, SEC or BIR certificate.',
        'The name on the documents does not match the agency or branch name. Please re-submit documents under the registered name.',
    ];

    private const BRANCH_REJECTION_REASONS = [
        'The branch address does not match the address on the submitted documents. Please update the address or re-submit matching documents.',
        'The TIN provided is incomplete or does not match the BIR certificate. Please correct it and re-submit.',
        'The submitted business document could not be verified. Please upload a clear copy and try again.',
    ];

    public function run(): void
    {
        $plans = Plan::all()->keyBy(fn($plan) => "{$plan->plan_code}-{$plan->type}");
        $modules = Module::all();
        $reference = Agency::whereNotNull('document')->first();
        $ownerPermissions = RoleEnum::AgencyOwner->permissions();
        $adminId = PlatformAdmin::query()->value('user_id');

        if ($plans->isEmpty() || $modules->isEmpty()) {
            $this->command->warn('Run PlanSeeder and ModuleSeeder first.');
            return;
        }

        $branchIndex = 0;

        foreach (self::SUBSCRIPTIONS as $index => $spec) {
            $number = $index + 1;
            [$first, $last, $agencyName] = self::OWNERS[$number - 1];
            $email = $number === 1
                ? 'princesestoso2@gmail.com'
                : "owner{$number}@example.com";

            DB::transaction(function () use (
                $spec,
                $number,
                $email,
                $first,
                $last,
                $agencyName,
                $plans,
                $modules,
                $reference,
                $ownerPermissions,
                $adminId,
                &$branchIndex
            ) {
                $plan = $plans["{$spec['plan']}-{$spec['type']}"];
                $includedBranches = Plan::branchLimitFor($spec['type']);
                $additionalBranches = max(0, $spec['branches'] - $includedBranches);
                $isPending = $spec['status'] === 'pending';
                $isRejected = $spec['status'] === 'rejected';

                $start = $isPending
                    ? Carbon::now()
                    : Carbon::now()->subDays(20 + $number * 17);
                $end = Subscription::termEnd($start);

                if (isset($spec['days_left'])) {
                    $end = Carbon::now()->addDays($spec['days_left']);
                    $start = $end->copy()->subYear();
                }

                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'password' => Hash::make('password'),
                        'provider' => 'local',
                    ]
                );

                $employee = Employee::updateOrCreate(
                    ['user_id' => $user->user_id],
                    [
                        'first_name' =>  $first,
                        'last_name' =>  $last,
                        'avatar' => 'https://ui-avatars.com/api/?name=' . strtoupper($first[0] . $last[0]),
                        'phone_number' => '917' . str_pad((string) (2000000 + $number), 7, '0', STR_PAD_LEFT),
                        'birth_date' => Carbon::now()->subYears(30 + $number)->toDateString(),
                    ]
                );

                [, $agencyStreet, $agencyCity, $agencyProvince, $agencyLatitude, $agencyLongitude] = self::BRANCHES[$branchIndex];

                $existingAgency = Agency::where('registered_by', $user->user_id)->first();

                $agencyLocation = $this->location($existingAgency?->location_id, [
                    'street' => $agencyStreet,
                    'city' => $agencyCity,
                    'province' => $agencyProvince,
                    'country' => 'Philippines',
                    'latitude' => $agencyLatitude,
                    'longitude' => $agencyLongitude,
                ]);

                $agency = Agency::updateOrCreate(
                    ['registered_by' => $user->user_id],
                    [
                        'name' => $agencyName,
                        'description' => "{$agencyName} provides dependable in-house and home-based care for seniors and patients who need daily support.",
                        'location_id' => $agencyLocation->location_id,
                        'email' => 'info' . $number . '@example.com',
                        // 'image' => 'https://ui-avatars.com/api/?size=512&background=random&color=fff&name=' . urlencode($agencyName),
                        'id_front' => $reference?->id_front,
                        'id_back' => $reference?->id_back,
                        'document' => $reference?->document,
                        'status' => match (true) {
                            $isPending => Agency::STATUS_PENDING,
                            $isRejected => Agency::STATUS_REJECTED,
                            default => Agency::STATUS_VERIFIED,
                        },
                    ]
                );

                $subscription = Subscription::updateOrCreate(
                    ['agency_id' => $agency->agency_id],
                    [
                        'plan_id' => $plan->plan_id,
                        'mode' => Subscription::MODE_LIVE,
                        'status' => match (true) {
                            $isPending => Subscription::STATUS_PENDING,
                            $isRejected => Subscription::STATUS_REJECTED,
                            default => Subscription::STATUS_ACTIVE,
                        },
                        'start_date' => $start,
                        'end_date' => $end,
                    ]
                );

                $paidByCard = $number % 3 !== 0;

                $existingPayment = SubscriptionPayment::where('subscription_id', $subscription->subscription_id)
                    ->where('type', SubscriptionPayment::TYPE_SUBSCRIPTION)
                    ->first();

                $payment = SubscriptionPayment::updateOrCreate(
                    [
                        'subscription_id' => $subscription->subscription_id,
                        'type' => SubscriptionPayment::TYPE_SUBSCRIPTION,
                    ],
                    [
                        'plan_id' => $plan->plan_id,
                        'xendit_invoice_id' => $existingPayment?->xendit_invoice_id ?? bin2hex(random_bytes(12)),
                        'payment_reference_id' => $existingPayment?->payment_reference_id ?? (string) Str::uuid(),
                        'masked_card_number' => $existingPayment?->masked_card_number
                            ?? '400000XXXXXX' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
                        'price' => (float) $plan->price + $additionalBranches * (float) $plan->additional_branch_price,
                        'status' => $isRejected
                            ? SubscriptionPayment::STATUS_REFUNDED
                            : SubscriptionPayment::STATUS_PAID,
                        'payment_method' => $paidByCard ? 'CREDIT-CARD' : 'GCASH',
                    ]
                );

                for ($n = 0; $n < $spec['branches']; $n++) {
                    $cosmeticIndex = $number * 100 + $n;
                    $isAmumaExtra = $number === 1 && $n > 0;

                    if ($isAmumaExtra) {
                        [$name, $street, $city, $province, $latitude, $longitude] = self::AMUMA_EXTRA_BRANCHES[$n - 1];
                    } else {
                        [$name, $street, $city, $province, $latitude, $longitude] = self::BRANCHES[$branchIndex];
                    }

                    $existingBranch = Branch::where('agency_id', $agency->agency_id)
                        ->where(function ($query) use ($name, $street, $city) {
                            $query->where('name', $name)
                                ->orWhereHas('location', fn($location) => $location
                                    ->where('street', $street)
                                    ->where('city', $city));
                        })
                        ->first();

                    $location = $this->location($existingBranch?->location_id, [
                        'street' => $street,
                        'city' => $city,
                        'province' => $province,
                        'country' => 'Philippines',
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                    ]);

                    $branchOverride = $spec['branch_statuses'][$n] ?? null;

                    $effectiveStatus = match (true) {
                        $isPending => 'pending',
                        $isRejected => 'rejected',
                        $branchOverride !== null => $branchOverride,
                        default => 'verified',
                    };

                    $branch = Branch::updateOrCreate(
                        ['branch_id' => $existingBranch?->branch_id],
                        [
                            'agency_id' => $agency->agency_id,
                            'location_id' => $location->location_id,
                            'name' => $name,
                            'email' => Str::slug($name) . '@example.com',
                            'description' => "{$name} offers compassionate caregiving with personalized support for daily living, personal care and companionship.",
                            'contact_number' => '9' . str_pad((string) (100000000 + $cosmeticIndex * 7919), 9, '0', STR_PAD_LEFT),
                            // 'image' => 'https://ui-avatars.com/api/?size=512&background=random&color=fff&name=' . urlencode($name),
                            'document' => $reference?->document,
                            'status' => match ($effectiveStatus) {
                                'pending' => Branch::STATUS_PENDING,
                                'rejected' => Branch::STATUS_REJECTED,
                                default => Branch::STATUS_VERIFIED,
                            },
                            'settings' => $this->settings($cosmeticIndex),
                        ]
                    );

                    $link = BranchSubscription::updateOrCreate(
                        [
                            'subscription_id' => $subscription->subscription_id,
                            'branch_id' => $branch->branch_id,
                        ],
                        [
                            'type' => $n < $includedBranches
                                ? BranchSubscription::TYPE_INCLUDED
                                : BranchSubscription::TYPE_ADDITIONAL,
                            'status' => match ($effectiveStatus) {
                                'pending' => BranchSubscription::STATUS_PENDING,
                                'rejected' => BranchSubscription::STATUS_REJECTED,
                                default => BranchSubscription::STATUS_APPROVED,
                            },
                        ]
                    );

                    $includesAgency = $n === 0 && ($isRejected || $effectiveStatus === 'verified');
                    $scope = $includesAgency ? VerificationLog::SCOPE_BOTH : VerificationLog::SCOPE_BRANCH;
                    $reasons = $includesAgency ? self::AGENCY_REJECTION_REASONS : self::BRANCH_REJECTION_REASONS;
                    $reason = $reasons[$cosmeticIndex % count($reasons)];

                    if ($link->wasRecentlyCreated && $effectiveStatus === 'verified' && $n > 0 && $cosmeticIndex % 3 === 0) {
                        $this->logDecision(
                            $link,
                            VerificationLog::ACTION_REJECTED,
                            VerificationLog::SCOPE_BRANCH,
                            self::BRANCH_REJECTION_REASONS[$cosmeticIndex % count(self::BRANCH_REJECTION_REASONS)],
                            $adminId,
                            $start->copy()->addHours(3 + $n),
                        );
                    }

                    if ($link->wasRecentlyCreated && $effectiveStatus !== 'pending') {
                        $this->logDecision(
                            $link,
                            $effectiveStatus === 'rejected'
                                ? VerificationLog::ACTION_REJECTED
                                : VerificationLog::ACTION_APPROVED,
                            $scope,
                            $effectiveStatus === 'rejected' ? $reason : null,
                            $adminId,
                            $start->copy()->addHours(6 + $n * 2),
                        );
                    }

                    if ($n === 0) {
                        EmployeeBranch::updateOrCreate(
                            [
                                'employee_id' => $employee->employee_id,
                                'branch_id' => $branch->branch_id,
                            ],
                            [
                                'role_name' => RoleEnum::AgencyOwner->value,
                                'status' => EmployeeBranch::STATUS_ACTIVE,
                            ]
                        );

                        foreach ($modules as $module) {
                            EmployeePermission::updateOrCreate(
                                [
                                    'employee_id' => $employee->employee_id,
                                    'branch_id' => $branch->branch_id,
                                    'module_id' => $module->module_id,
                                ],
                                EmployeePermission::grantColumns($ownerPermissions[$module->module_name] ?? [])
                            );
                        }
                    }

                    DB::table('branches')
                        ->where('branch_id', $branch->branch_id)
                        ->update(['created_at' => $start, 'updated_at' => $start]);

                    if (!$isAmumaExtra) {
                        $branchIndex++;
                    }
                }

                DB::table('subscriptions')
                    ->where('subscription_id', $subscription->subscription_id)
                    ->update(['created_at' => $start, 'updated_at' => $start]);

                DB::table('subscription_payments')
                    ->where('subscription_payment_id', $payment->subscription_payment_id)
                    ->update(['created_at' => $start, 'updated_at' => $start]);

                DB::table('agencies')
                    ->where('agency_id', $agency->agency_id)
                    ->update(['created_at' => $start, 'updated_at' => $start]);

                DB::table('branch_subscription')
                    ->where('subscription_id', $subscription->subscription_id)
                    ->update(['created_at' => $start, 'updated_at' => $start]);
            });
        }
    }

    private function location(?int $id, array $attributes): Location
    {
        $location = $id ? Location::find($id) : null;

        if ($location) {
            $location->update($attributes);

            return $location;
        }

        return Location::create($attributes);
    }

    private function logDecision(
        BranchSubscription $link,
        string $action,
        string $scope,
        ?string $reason,
        ?int $adminId,
        Carbon $at,
    ): void {
        $log = new VerificationLog([
            'branch_subscription_id' => $link->branch_subscription_id,
            'action' => $action,
            'scope' => $scope,
            'reason' => $reason,
            'action_by' => $adminId,
        ]);

        $log->created_at = $at;
        $log->updated_at = $at;
        $log->save();
    }

    private function settings(int $index): array
    {
        $hours = [
            ['00:00', '23:59'],
            ['06:00', '22:00'],
            ['07:00', '21:00'],
            ['08:00', '20:00'],
        ][$index % 4];

        return [
            'currency' => 'PHP',
            'opening' => $hours[0],
            'closing' => $hours[1],
            'time_zone' => 'Asia/Manila',
            'reserved_walkin_slots' => (string) (2 + $index % 4),
            'enable_booking_pre_admission' => '1',
            'enable_booking_complete_admission' => $index % 3 === 0 ? '0' : '1',
            'requires_full_payment_on_admit' => $index % 2 === 0 ? '1' : '0',
            'complete_admission_booking_percent' => (string) [100, 50, 30][$index % 3],
            'homecare_downpayment_percent' => (string) [50, 30, 50][$index % 3],
            'minimum_adl_hours' => (string) [8, 6, 4][$index % 3],
            'tin' => sprintf('%03d-%03d-%03d-%03d', 100 + $index, 200 + $index, 300 + $index, 1),
            'is_open' => '1',
        ];
    }
}
