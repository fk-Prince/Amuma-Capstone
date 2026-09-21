<?php

namespace Database\Seeders;

use App\Enums\BillingIntervalEnum;
use App\Enums\RoleEnum;
use App\Models\Agency;
use App\Models\Branch;
use App\Models\BranchSubscription;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\EmployeePermission;
use App\Models\Location;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BranchSubscriptionSeeder extends Seeder
{
    private const SUBSCRIPTIONS = [
        ['plan' => 'C', 'branches' => 3, 'interval' => 'YEARLY', 'status' => 'active'],
        ['plan' => 'B', 'branches' => 2, 'interval' => 'MONTHLY', 'status' => 'active'],
        ['plan' => 'C', 'branches' => 1, 'interval' => 'YEARLY', 'status' => 'active'],
        ['plan' => 'C', 'branches' => 2, 'interval' => 'MONTHLY', 'status' => 'active'],
        ['plan' => 'A', 'branches' => 2, 'interval' => 'MONTHLY', 'status' => 'active'],
        ['plan' => 'B', 'branches' => 1, 'interval' => 'YEARLY', 'status' => 'rejected'],
        ['plan' => 'C', 'branches' => 3, 'interval' => 'YEARLY', 'status' => 'active'],
        ['plan' => 'A', 'branches' => 2, 'interval' => 'YEARLY', 'status' => 'active'],
        ['plan' => 'B', 'branches' => 1, 'interval' => 'MONTHLY', 'status' => 'pending'],
        ['plan' => 'C', 'branches' => 3, 'interval' => 'MONTHLY', 'status' => 'active'],
    ];

    private const OWNERS = [
        ['Marissa', 'Villanueva', 'Sunrise Elder Care'],
        ['Rodrigo', 'Dela Pena', 'Golden Years Care Group'],
        ['Angelica', 'Bautista', 'Harmony Home Health'],
        ['Emmanuel', 'Santiago', 'Bayanihan Care Network'],
        ['Cristina', 'Mercado', 'Serenity Senior Living'],
        ['Joaquin', 'Aquino', 'CareBridge Philippines'],
        ['Patricia', 'Navarro', 'Tahanan Wellness Group'],
        ['Benedict', 'Salazar', 'Silver Oak Care'],
        ['Leonora', 'Castillo', 'Kalinga Care Partners'],
        ['Alfredo', 'Domingo', 'Mabuhay Health Services'],
    ];

    private const BRANCHES = [
        ['Sunrise Butuan', 'Butuan City', 'Agusan del Norte', 8.9475, 125.5406],
        ['Sunrise Digos', 'Digos City', 'Davao del Sur', 6.7497, 125.3572],
        ['Sunrise Tagum', 'Tagum City', 'Davao del Norte', 7.4478, 125.8078],
        ['Golden Years Cebu', 'Cebu City', 'Cebu', 10.3157, 123.8854],
        ['Golden Years Mandaue', 'Mandaue City', 'Cebu', 10.3236, 123.9223],
        ['Harmony Quezon City', 'Quezon City', 'Metro Manila', 14.6760, 121.0437],
        ['Bayanihan Makati', 'Makati City', 'Metro Manila', 14.5547, 121.0244],
        ['Bayanihan Pasig', 'Pasig City', 'Metro Manila', 14.5764, 121.0851],
        ['Serenity Iloilo', 'Iloilo City', 'Iloilo', 10.7202, 122.5621],
        ['Serenity Bacolod', 'Bacolod City', 'Negros Occidental', 10.6407, 122.9689],
        ['CareBridge Cagayan de Oro', 'Cagayan de Oro', 'Misamis Oriental', 8.4542, 124.6319],
        ['Tahanan Baguio', 'Baguio City', 'Benguet', 16.4023, 120.5960],
        ['Tahanan La Union', 'San Fernando', 'La Union', 16.6159, 120.3209],
        ['Tahanan Angeles', 'Angeles City', 'Pampanga', 15.1450, 120.5887],
        ['Silver Oak General Santos', 'General Santos', 'South Cotabato', 6.1164, 125.1716],
        ['Silver Oak Koronadal', 'Koronadal', 'South Cotabato', 6.5031, 124.8469],
        ['Kalinga Zamboanga', 'Zamboanga City', 'Zamboanga del Sur', 6.9214, 122.0790],
        ['Kalinga Dipolog', 'Dipolog City', 'Zamboanga del Norte', 8.5886, 123.3409],
        ['Mabuhay Naga', 'Naga City', 'Camarines Sur', 13.6218, 123.1948],
        ['Mabuhay Legazpi', 'Legazpi City', 'Albay', 13.1391, 123.7438],
    ];

    private const STREETS = [
        'Rizal Street',
        'Quezon Avenue',
        'Mabini Street',
        'Bonifacio Street',
        'Osmena Boulevard',
        'Roxas Avenue',
        'Magsaysay Street',
        'Del Pilar Street',
    ];

    public function run(): void
    {
        $plans = Plan::all()->keyBy('plan_code');
        $modules = Module::all();
        $reference = Agency::whereNotNull('document')->first();
        $ownerPermissions = RoleEnum::BranchOwner->permissions();

        if ($plans->isEmpty() || $modules->isEmpty()) {
            $this->command->warn('Run PlanSeeder and ModuleSeeder first.');
            return;
        }

        $branchIndex = 0;

        foreach (self::SUBSCRIPTIONS as $index => $spec) {
            $number = $index + 1;
            $email = "seed.owner{$number}@example.com";

            if (User::where('email', $email)->exists()) {
                $branchIndex += $spec['branches'];
                continue;
            }

            DB::transaction(function () use (
                $spec,
                $number,
                $email,
                $plans,
                $modules,
                $reference,
                $ownerPermissions,
                &$branchIndex
            ) {
                [$first, $last, $agencyName] = self::OWNERS[$number - 1];
                $plan = $plans[$spec['plan']];
                $interval = BillingIntervalEnum::from($spec['interval']);
                $isPending = $spec['status'] === 'pending';
                $isRejected = $spec['status'] === 'rejected';
                $isApproved = !$isPending && !$isRejected;

                $start = $isPending
                    ? Carbon::now()
                    : Carbon::now()->subDays(
                        $spec['interval'] === 'YEARLY'
                            ? 20 + $number * 17
                            : 2 + $number * 2
                    );
                $end = $interval->addTo($start);

                $user = User::create([
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'provider' => 'local',
                ]);

                $client = Client::create([
                    'user_id' => $user->user_id,
                    'first_name' => $first,
                    'last_name' => $last,
                    'phone_number' => '917' . str_pad((string) (2000000 + $number), 7, '0', STR_PAD_LEFT),
                    'avatar' => 'https://ui-avatars.com/api/?name=' . strtoupper($first[0] . $last[0]),
                ]);

                $employee = Employee::create([
                    'user_id' => $user->user_id,
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'avatar' => $client->avatar,
                    'phone_number' => $client->phone_number,
                    'status' => Employee::STATUS_ACTIVE,
                    'birth_date' => Carbon::now()->subYears(30 + $number)->toDateString(),
                ]);

                $agencyLocation = Location::create([
                    'street' => self::STREETS[$number % count(self::STREETS)],
                    'city' => self::BRANCHES[$branchIndex][1],
                    'province' => self::BRANCHES[$branchIndex][2],
                    'country' => 'Philippines',
                    'latitude' => self::BRANCHES[$branchIndex][3],
                    'longitude' => self::BRANCHES[$branchIndex][4],
                ]);

                $agency = Agency::create([
                    'name' => $agencyName,
                    'description' => "{$agencyName} provides dependable in-house and home-based care for seniors and patients who need daily support.",
                    'location_id' => $agencyLocation->location_id,
                    'registered_by' => $user->user_id,
                    'email' => 'info' . $number . '@example.com',
                    'image' => 'https://ui-avatars.com/api/?size=512&background=random&color=fff&name=' . urlencode($agencyName),
                    'id_front' => $reference?->id_front,
                    'id_back' => $reference?->id_back,
                    'document' => $reference?->document,
                    'is_verified' => $isApproved,
                ]);

                $subscription = Subscription::create([
                    'plan_id' => $plan->plan_id,
                    'agency_id' => $agency->agency_id,
                    'status' => match (true) {
                        $isPending => Subscription::STATUS_PENDING,
                        $isRejected => Subscription::STATUS_REJECTED,
                        default => Subscription::STATUS_ACTIVE,
                    },
                    'start_date' => $start,
                    'end_date' => $end,
                ]);

                $paidByCard = $number % 3 !== 0;

                $payment = SubscriptionPayment::create([
                    'subscription_id' => $subscription->subscription_id,
                    'plan_id' => $plan->plan_id,
                    'xendit_invoice_id' => bin2hex(random_bytes(12)),
                    'payment_reference_id' => (string) Str::uuid(),
                    'masked_card_number' => $paidByCard
                        ? '400000XXXXXX' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT)
                        : null,
                    'price' => (float) $plan->{$interval->loadPriceKey()},
                    'status' => $isRejected
                        ? SubscriptionPayment::STATUS_REFUNDED
                        : SubscriptionPayment::STATUS_PAID,
                    'type' => SubscriptionPayment::TYPE_SUBSCRIPTION,
                    'billing_interval' => $interval->value,
                    'payment_method' => $paidByCard ? 'CREDIT-CARD' : 'GCASH',
                ]);

                for ($n = 0; $n < $spec['branches']; $n++) {
                    [$name, $city, $province, $latitude, $longitude] = self::BRANCHES[$branchIndex];

                    $location = Location::create([
                        'street' => self::STREETS[($branchIndex + $n) % count(self::STREETS)],
                        'city' => $city,
                        'province' => $province,
                        'country' => 'Philippines',
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                    ]);

                    $branch = Branch::create([
                        'agency_id' => $agency->agency_id,
                        'location_id' => $location->location_id,
                        'name' => $name,
                        'email' => Str::slug($name) . '@example.com',
                        'description' => "{$name} offers compassionate caregiving with personalized support for daily living, personal care and companionship.",
                        'contact_number' => '9' . str_pad((string) (100000000 + $branchIndex * 7919), 9, '0', STR_PAD_LEFT),
                        'image' => 'https://ui-avatars.com/api/?size=512&background=random&color=fff&name=' . urlencode($name),
                        'document' => $reference?->document,
                        'is_verified' => $isApproved,
                        'settings' => $this->settings($branchIndex),
                    ]);

                    BranchSubscription::create([
                        'subscription_id' => $subscription->subscription_id,
                        'branch_id' => $branch->branch_id,
                        'status' => match (true) {
                            $isPending => BranchSubscription::STATUS_PENDING,
                            $isRejected => BranchSubscription::STATUS_REJECTED,
                            default => BranchSubscription::STATUS_APPROVED,
                        },
                        'rejection_reason' => $isRejected
                            ? 'The submitted business document could not be verified. Please upload a clear copy and try again.'
                            : null,
                    ]);

                    EmployeeBranch::create([
                        'employee_id' => $employee->employee_id,
                        'branch_id' => $branch->branch_id,
                        'role_name' => 'branch_owner',
                    ]);

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

                    DB::table('branches')
                        ->where('branch_id', $branch->branch_id)
                        ->update(['created_at' => $start, 'updated_at' => $start]);

                    $branchIndex++;
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

        $this->command->info(
            'Seeded ' . Branch::count() . ' branches across ' . Subscription::count() . ' subscriptions.'
        );
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
            'minimum_adl_hours' => (string) [8, 6, 4][$index % 3],
            'tin' => sprintf('%03d-%03d-%03d-%03d', 100 + $index, 200 + $index, 300 + $index, 1),
            'is_open' => '1',
        ];
    }
}
