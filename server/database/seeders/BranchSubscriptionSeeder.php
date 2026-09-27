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
        ['plan' => 'C', 'branches' => 3, 'interval' => 'YEARLY', 'status' => 'active', 'branch_statuses' => [1 => 'pending', 2 => 'rejected']],
        ['plan' => 'C', 'branches' => 3, 'interval' => 'YEARLY', 'status' => 'active'],
        ['plan' => 'B', 'branches' => 2, 'interval' => 'MONTHLY', 'status' => 'active', 'days_left' => 5],
        ['plan' => 'C', 'branches' => 1, 'interval' => 'YEARLY', 'status' => 'active'],
        ['plan' => 'C', 'branches' => 2, 'interval' => 'MONTHLY', 'status' => 'active'],
        ['plan' => 'A', 'branches' => 2, 'interval' => 'MONTHLY', 'status' => 'active'],
        ['plan' => 'B', 'branches' => 1, 'interval' => 'YEARLY', 'status' => 'rejected'],
        ['plan' => 'C', 'branches' => 3, 'interval' => 'YEARLY', 'status' => 'active'],
        ['plan' => 'A', 'branches' => 2, 'interval' => 'YEARLY', 'status' => 'active'],
        ['plan' => 'B', 'branches' => 1, 'interval' => 'MONTHLY', 'status' => 'pending'],
        ['plan' => 'C', 'branches' => 3, 'interval' => 'MONTHLY', 'status' => 'active', 'days_left' => 2],
        ['plan' => 'A', 'branches' => 2, 'interval' => 'MONTHLY', 'status' => 'active'],
        ['plan' => 'B', 'branches' => 1, 'interval' => 'YEARLY', 'status' => 'pending'],
        ['plan' => 'C', 'branches' => 2, 'interval' => 'MONTHLY', 'status' => 'rejected'],
        ['plan' => 'C', 'branches' => 2, 'interval' => 'MONTHLY', 'status' => 'active'],
        ['plan' => 'B', 'branches' => 1, 'interval' => 'YEARLY', 'status' => 'pending'],
        ['plan' => 'A', 'branches' => 2, 'interval' => 'MONTHLY', 'status' => 'pending'],
        ['plan' => 'C', 'branches' => 3, 'interval' => 'YEARLY', 'status' => 'active'],
        ['plan' => 'B', 'branches' => 1, 'interval' => 'MONTHLY', 'status' => 'rejected'],
        ['plan' => 'C', 'branches' => 2, 'interval' => 'YEARLY', 'status' => 'pending'],
        ['plan' => 'A', 'branches' => 1, 'interval' => 'MONTHLY', 'status' => 'active'],
        ['plan' => 'B', 'branches' => 1, 'interval' => 'YEARLY', 'status' => 'pending'],
        ['plan' => 'C', 'branches' => 3, 'interval' => 'MONTHLY', 'status' => 'active'],
        ['plan' => 'A', 'branches' => 2, 'interval' => 'YEARLY', 'status' => 'pending'],
        ['plan' => 'B', 'branches' => 1, 'interval' => 'MONTHLY', 'status' => 'rejected'],
        ['plan' => 'C', 'branches' => 2, 'interval' => 'YEARLY', 'status' => 'active'],
        ['plan' => 'A', 'branches' => 1, 'interval' => 'MONTHLY', 'status' => 'pending'],
        ['plan' => 'B', 'branches' => 1, 'interval' => 'YEARLY', 'status' => 'pending'],
        ['plan' => 'C', 'branches' => 2, 'interval' => 'MONTHLY', 'status' => 'active'],
        ['plan' => 'A', 'branches' => 3, 'interval' => 'YEARLY', 'status' => 'pending'],
        ['plan' => 'B', 'branches' => 1, 'interval' => 'MONTHLY', 'status' => 'rejected'],
        ['plan' => 'C', 'branches' => 2, 'interval' => 'YEARLY', 'status' => 'pending'],
        ['plan' => 'A', 'branches' => 1, 'interval' => 'MONTHLY', 'status' => 'pending'],
        ['plan' => 'B', 'branches' => 2, 'interval' => 'YEARLY', 'status' => 'pending'],
        ['plan' => 'A', 'branches' => 1, 'interval' => 'MONTHLY', 'status' => 'pending'],
        ['plan' => 'B', 'branches' => 1, 'interval' => 'YEARLY', 'status' => 'rejected'],
    ];

    private const OWNERS = [
        ['Prince2', 'Sestoso', 'AMUMA'],
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
        ['Renato', 'Cabrera', 'Malasakit Home Care'],
        ['Divina', 'Reyes', 'Payapa Elder Services'],
        ['Herminio', 'Flores', 'Ligaya Caregiving'],
        ['Ramon', 'Villareal', 'Malaya Caregivers'],
        ['Corazon', 'Ibanez', 'Ginhawa Senior Care'],
        ['Teodoro', 'Manalo', 'Lakbay Elderly Services'],
        ['Josefina', 'Cruz', 'Tahimik Home Health'],
        ['Bienvenido', 'Torres', 'Kapwa Care Group'],
        ['Remedios', 'Aguilar', 'Bahaghari Senior Living'],
        ['Fernando', 'Pascual', 'Alagang Pinoy Care'],
        ['Consolacion', 'Rivera', 'Malinis Home Care'],
        ['Wilfredo', 'Santos', 'Ligtas Elder Services'],
        ['Perpetua', 'Gonzales', 'Damayan Caregiving'],
        ['Rogelio', 'Ramos', 'Tibay Senior Care'],
        ['Milagros', 'Fernandez', 'Sinag Home Health'],
        ['Eduardo', 'Lopez', 'Ganda Elderly Care'],
        ['Purificacion', 'Ocampo', 'Bayan Caregivers'],
        ['Anacleto', 'Delacruz', 'Pag-asa Senior Living'],
        ['Felicidad', 'Garcia', 'Sigla Home Care'],
        ['Marcelo', 'Reyes', 'Tatag Elder Services'],
        ['Dolores', 'Villanueva', 'Kalusugan Caregiving'],
        ['Genaro', 'Mendoza', 'Liwanag Senior Care'],
        ['Trinidad', 'Bautista', 'Bukas Home Health'],
        ['Alicia', 'Mendez', 'Sagip Elder Care'],
        ['Ruben', 'Castro', 'Tanglaw Home Health'],
    ];

    private const AMUMA_EXTRA_BRANCHES = [
        ['AMUMA Panabo', 'Panabo City', 'Davao del Norte', 7.3086, 125.6844],
        ['AMUMA Samal', 'Island Garden City of Samal', 'Davao del Norte', 7.0472, 125.7139],
    ];

    private const BRANCHES = [
        ['AMUMA Davao', 'Davao City', 'Davao del Sur', 7.1907, 125.4553],
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
        ['Malasakit Iligan', 'Iligan City', 'Lanao del Norte', 8.2280, 124.2452],
        ['Malasakit Ozamiz', 'Ozamiz City', 'Misamis Occidental', 8.1500, 123.8437],
        ['Payapa Roxas', 'Roxas City', 'Capiz', 11.5853, 122.7511],
        ['Ligaya Dumaguete', 'Dumaguete City', 'Negros Oriental', 9.3103, 123.3080],
        ['Ligaya Tacloban', 'Tacloban City', 'Leyte', 11.2543, 125.0000],
        ['Malaya Malolos', 'Malolos City', 'Bulacan', 14.8433, 120.8114],
        ['Malaya San Jose Del Monte', 'San Jose Del Monte', 'Bulacan', 14.8136, 121.0453],
        ['Ginhawa Antipolo', 'Antipolo City', 'Rizal', 14.5878, 121.1760],
        ['Lakbay Bacoor', 'Bacoor City', 'Cavite', 14.4624, 120.8967],
        ['Lakbay Imus', 'Imus City', 'Cavite', 14.4297, 120.9367],
        ['Tahimik Dasmarinas', 'Dasmarinas City', 'Cavite', 14.3294, 120.9367],
        ['Tahimik San Pedro', 'San Pedro City', 'Laguna', 14.3583, 121.0583],
        ['Tahimik Binan', 'Binan City', 'Laguna', 14.3333, 121.0833],
        ['Kapwa Santa Rosa', 'Santa Rosa City', 'Laguna', 14.3122, 121.1114],
        ['Bahaghari Lipa', 'Lipa City', 'Batangas', 13.9411, 121.1622],
        ['Bahaghari Batangas City', 'Batangas City', 'Batangas', 13.7565, 121.0583],
        ['Alagang Lucena', 'Lucena City', 'Quezon', 13.9373, 121.6174],
        ['Malinis Puerto Princesa', 'Puerto Princesa City', 'Palawan', 9.7392, 118.7353],
        ['Ligtas Kalibo', 'Kalibo', 'Aklan', 11.7079, 122.3626],
        ['Ligtas Ormoc', 'Ormoc City', 'Leyte', 11.0064, 124.6075],
        ['Ligtas Bislig', 'Bislig City', 'Surigao del Sur', 8.2150, 126.3183],
        ['Damayan Surigao', 'Surigao City', 'Surigao del Norte', 9.7833, 125.4917],
        ['Damayan Cotabato City', 'Cotabato City', 'Maguindanao', 7.2231, 124.2452],
        ['Tibay Marawi', 'Marawi City', 'Lanao del Sur', 8.0000, 124.2903],
        ['Sinag Pagadian', 'Pagadian City', 'Zamboanga del Sur', 7.8257, 123.4373],
        ['Sinag Tuguegarao', 'Tuguegarao City', 'Cagayan', 17.6132, 121.7270],
        ['Ganda Ilagan', 'Ilagan City', 'Isabela', 17.1497, 121.8892],
        ['Bayan Vigan', 'Vigan City', 'Ilocos Sur', 17.5747, 120.3869],
        ['Pag-asa Laoag', 'Laoag City', 'Ilocos Norte', 18.1978, 120.5936],
        ['Pag-asa Dagupan', 'Dagupan City', 'Pangasinan', 16.0433, 120.3333],
        ['Sigla San Fernando', 'San Fernando City', 'Pampanga', 15.0286, 120.6897],
        ['Sigla Tarlac', 'Tarlac City', 'Tarlac', 15.4802, 120.5979],
        ['Sigla Cabanatuan', 'Cabanatuan City', 'Nueva Ecija', 15.4869, 120.9683],
        ['Tatag Olongapo', 'Olongapo City', 'Zambales', 14.8294, 120.2830],
        ['Kalusugan Alaminos', 'Alaminos City', 'Pangasinan', 16.1553, 119.9784],
        ['Kalusugan Urdaneta', 'Urdaneta City', 'Pangasinan', 15.9761, 120.5701],
        ['Liwanag Malaybalay', 'Malaybalay City', 'Bukidnon', 8.1575, 125.1278],
        ['Bukas Valencia', 'Valencia City', 'Bukidnon', 7.9061, 125.0947],
        ['Bukas Tandag', 'Tandag City', 'Surigao del Sur', 9.0785, 126.1989],
        ['Sagip Cabuyao', 'Cabuyao City', 'Laguna', 14.2786, 121.1189],
        ['Tanglaw Meycauayan', 'Meycauayan City', 'Bulacan', 14.7365, 120.9583],
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
        $ownerPermissions = RoleEnum::BranchManager->permissions();

        if ($plans->isEmpty() || $modules->isEmpty()) {
            $this->command->warn('Run PlanSeeder and ModuleSeeder first.');
            return;
        }

        $branchIndex = 0;

        foreach (self::SUBSCRIPTIONS as $index => $spec) {
            $number = $index + 1;
            [$first, $last, $agencyName] = self::OWNERS[$number - 1];
            $email = $agencyName === 'AMUMA'
                ? 'princesestoso2@gmail.com'
                : "owner{$number}@example.com";

            $sharedBranchesUsed = $spec['branches'] - ($agencyName === 'AMUMA' ? count(self::AMUMA_EXTRA_BRANCHES) : 0);

            if (User::where('email', $email)->exists()) {
                $branchIndex += $sharedBranchesUsed;
                continue;
            }

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
                &$branchIndex
            ) {
                $plan = $plans[$spec['plan']];
                $interval = BillingIntervalEnum::from($spec['interval']);
                $isPending = $spec['status'] === 'pending';
                $isRejected = $spec['status'] === 'rejected';

                $start = $isPending
                    ? Carbon::now()
                    : Carbon::now()->subDays(
                        $spec['interval'] === 'YEARLY'
                            ? 20 + $number * 17
                            : 2 + $number * 2
                    );
                $end = $interval->addTo($start);

                if (isset($spec['days_left'])) {
                    $end = Carbon::now()->addDays($spec['days_left']);
                    $start = $spec['interval'] === 'YEARLY'
                        ? $end->copy()->subYear()
                        : $end->copy()->subMonth();
                }

                $user = User::create([
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'provider' => 'local',
                ]);

                $employee = Employee::create([
                    'user_id' => $user->user_id,
                    'first_name' =>  $first,
                    'last_name' =>  $last,
                    'avatar' => 'https://ui-avatars.com/api/?name=' . strtoupper($first[0] . $last[0]),
                    'phone_number' => '917' . str_pad((string) (2000000 + $number), 7, '0', STR_PAD_LEFT),
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
                    // 'image' => 'https://ui-avatars.com/api/?size=512&background=random&color=fff&name=' . urlencode($agencyName),
                    'id_front' => $reference?->id_front,
                    'id_back' => $reference?->id_back,
                    'document' => $reference?->document,
                    'status' => match (true) {
                        $isPending => Agency::STATUS_PENDING,
                        $isRejected => Agency::STATUS_REJECTED,
                        default => Agency::STATUS_VERIFIED,
                    },
                ]);

                $subscription = Subscription::create([
                    'plan_id' => $plan->plan_id,
                    'agency_id' => $agency->agency_id,
                    'status' => match (true) {
                        $isPending => Subscription::STATUS_PENDING,
                        $isRejected => Subscription::STATUS_REJECTED,
                        default => Subscription::STATUS_ACTIVE,
                    },
                    'billing_interval' => $interval->value,
                    'start_date' => $start,
                    'end_date' => $end,
                ]);

                $paidByCard = $number % 3 !== 0;

                $payment = SubscriptionPayment::create([
                    'subscription_id' => $subscription->subscription_id,
                    'plan_id' => $plan->plan_id,
                    'xendit_invoice_id' => bin2hex(random_bytes(12)),
                    'payment_reference_id' => (string) Str::uuid(),
                    'masked_card_number' => '400000XXXXXX' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
                    'price' => (float) $plan->{$interval->loadPriceKey()},
                    'status' => $isRejected
                        ? SubscriptionPayment::STATUS_REFUNDED
                        : SubscriptionPayment::STATUS_PAID,
                    'type' => SubscriptionPayment::TYPE_SUBSCRIPTION,
                    'billing_interval' => $interval->value,
                    'payment_method' => $paidByCard ? 'CREDIT-CARD' : 'GCASH',
                ]);

                $lastBranchRejected = !$isPending
                    && !$isRejected
                    && $spec['branches'] > 1
                    && $number % 2 === 0;

                for ($n = 0; $n < $spec['branches']; $n++) {
                    $cosmeticIndex = $number * 10 + $n;
                    $isAmumaExtra = $agencyName === 'AMUMA' && $n > 0;

                    if ($isAmumaExtra) {
                        [$name, $city, $province, $latitude, $longitude] = self::AMUMA_EXTRA_BRANCHES[$n - 1];
                    } else {
                        [$name, $city, $province, $latitude, $longitude] = self::BRANCHES[$branchIndex];
                    }

                    $location = Location::create([
                        'street' => self::STREETS[$cosmeticIndex % count(self::STREETS)],
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
                        $lastBranchRejected && $n === $spec['branches'] - 1 => 'rejected',
                        default => 'verified',
                    };

                    $branch = Branch::create([
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
                    ]);

                    BranchSubscription::create([
                        'subscription_id' => $subscription->subscription_id,
                        'branch_id' => $branch->branch_id,
                        'status' => match ($effectiveStatus) {
                            'pending' => BranchSubscription::STATUS_PENDING,
                            'rejected' => BranchSubscription::STATUS_REJECTED,
                            default => BranchSubscription::STATUS_APPROVED,
                        },
                        'rejection_reason' => $effectiveStatus === 'rejected'
                            ? 'The submitted business document could not be verified. Please upload a clear copy and try again.'
                            : null,
                    ]);

                    if ($n === 0) {
                        EmployeeBranch::create([
                            'employee_id' => $employee->employee_id,
                            'branch_id' => $branch->branch_id,
                            'role_name' => RoleEnum::AgencyOwner->value,
                            'status' => EmployeeBranch::STATUS_ACTIVE,
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
