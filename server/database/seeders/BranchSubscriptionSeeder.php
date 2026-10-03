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
        ['plan' => 'C', 'branches' => 3, 'status' => 'active', 'branch_statuses' => [1 => 'pending', 2 => 'rejected']],
        ['plan' => 'C', 'branches' => 3, 'status' => 'active'],
        ['plan' => 'B', 'branches' => 2, 'status' => 'active', 'days_left' => 5],
        ['plan' => 'C', 'branches' => 1, 'status' => 'active'],
        ['plan' => 'C', 'branches' => 2, 'status' => 'active'],
        ['plan' => 'A', 'branches' => 2, 'status' => 'active'],
        ['plan' => 'B', 'branches' => 1, 'status' => 'rejected'],
        ['plan' => 'C', 'branches' => 3, 'status' => 'active'],
        ['plan' => 'A', 'branches' => 2, 'status' => 'active'],
        ['plan' => 'B', 'branches' => 1, 'status' => 'pending'],
        ['plan' => 'C', 'branches' => 3, 'status' => 'active', 'days_left' => 2],
        ['plan' => 'A', 'branches' => 2, 'status' => 'active'],
        ['plan' => 'B', 'branches' => 1, 'status' => 'pending'],
        ['plan' => 'C', 'branches' => 2, 'status' => 'rejected'],
        ['plan' => 'C', 'branches' => 2, 'status' => 'active'],
        ['plan' => 'B', 'branches' => 1, 'status' => 'pending'],
        ['plan' => 'A', 'branches' => 2, 'status' => 'pending'],
        ['plan' => 'C', 'branches' => 3, 'status' => 'active'],
        ['plan' => 'B', 'branches' => 1, 'status' => 'rejected'],
        ['plan' => 'C', 'branches' => 2, 'status' => 'pending'],
        ['plan' => 'A', 'branches' => 1, 'status' => 'active'],
        ['plan' => 'B', 'branches' => 1, 'status' => 'pending'],
        ['plan' => 'C', 'branches' => 3, 'status' => 'active'],
        ['plan' => 'A', 'branches' => 2, 'status' => 'pending'],
        ['plan' => 'B', 'branches' => 1, 'status' => 'rejected'],
        ['plan' => 'C', 'branches' => 2, 'status' => 'active'],
        ['plan' => 'A', 'branches' => 1, 'status' => 'pending'],
        ['plan' => 'B', 'branches' => 1, 'status' => 'pending'],
        ['plan' => 'C', 'branches' => 2, 'status' => 'active'],
        ['plan' => 'A', 'branches' => 3, 'status' => 'pending'],
        ['plan' => 'B', 'branches' => 1, 'status' => 'rejected'],
        ['plan' => 'C', 'branches' => 2, 'status' => 'pending'],
        ['plan' => 'A', 'branches' => 1, 'status' => 'pending'],
        ['plan' => 'B', 'branches' => 2, 'status' => 'pending'],
        ['plan' => 'A', 'branches' => 1, 'status' => 'pending'],
        ['plan' => 'B', 'branches' => 1, 'status' => 'rejected'],
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
        ['AMUMA Panabo', 'Rizal Street', 'Panabo City', 'Davao del Norte', 7.2995764, 125.6814238],
        ['AMUMA Samal', 'Purok 2, Circumferential Road', 'Samal', 'Davao del Norte', 7.0560898, 125.7211018],
    ];

    private const BRANCHES = [
        ['AMUMA Davao', 'Rizal Street', 'Davao City', 'Davao del Sur', 7.0760930, 125.6015037],
        ['Sunrise Butuan', 'Quezon Avenue', 'Butuan City', 'Agusan del Norte', 8.9477147, 125.5432054],
        ['Sunrise Digos', 'Bonifacio Street', 'Digos City', 'Davao del Sur', 6.7422588, 125.3662853],
        ['Sunrise Tagum', 'Roxas Avenue', 'Tagum City', 'Davao del Norte', 7.4470784, 125.8094853],
        ['Golden Years Cebu', 'Osmena Boulevard', 'Cebu City', 'Cebu', 10.3081889, 123.8936359],
        ['Golden Years Mandaue', 'Magsaysay Street', 'Mandaue City', 'Cebu', 10.3269049, 123.9427295],
        ['Harmony Quezon City', 'Magsaysay Street', 'Quezon City', 'Metro Manila', 14.6385123, 121.0669410],
        ['Bayanihan Makati', 'Del Pilar Street', 'Makati City', 'Metro Manila', 14.5419763, 121.0121495],
        ['Bayanihan Pasig', 'Quezon Avenue', 'Pasig City', 'Metro Manila', 14.5558469, 121.0894160],
        ['Serenity Iloilo', 'Quezon Avenue', 'Iloilo City', 'Iloilo', 10.7131258, 122.5626690],
        ['Serenity Bacolod', 'Bonifacio Street', 'Bacolod City', 'Negros Occidental', 10.6762836, 122.9513786],
        ['CareBridge Cagayan de Oro', 'Bonifacio Street', 'Cagayan de Oro', 'Misamis Oriental', 8.4756417, 124.6421532],
        ['Tahanan Baguio', 'Osmena Boulevard', 'Baguio City', 'Benguet', 16.4119860, 120.5933878],
        ['Tahanan La Union', 'Magsaysay Street', 'San Fernando', 'La Union', 16.6162676, 120.3171040],
        ['Tahanan Angeles', 'Rizal Street', 'Angeles City', 'Pampanga', 15.1348084, 120.5906946],
        ['Silver Oak General Santos', 'Del Pilar Street', 'General Santos', 'South Cotabato', 6.1122217, 125.1721893],
        ['Silver Oak Koronadal', 'Quezon Avenue', 'Koronadal', 'South Cotabato', 6.5004041, 124.8435437],
        ['Kalinga Zamboanga', 'Quezon Avenue', 'Zamboanga City', 'Zamboanga del Sur', 6.9046876, 122.0764868],
        ['Kalinga Dipolog', 'Mabini Street', 'Dipolog City', 'Zamboanga del Norte', 8.5879253, 123.3438302],
        ['Mabuhay Naga', 'Osmena Boulevard', 'Naga City', 'Camarines Sur', 13.6240122, 123.1850318],
        ['Mabuhay Legazpi', 'Magsaysay Street', 'Legazpi City', 'Albay', 13.1388505, 123.7345746],
        ['Malasakit Iligan', 'Roxas Avenue', 'Iligan City', 'Lanao del Norte', 8.2281556, 124.2411508],
        ['Malasakit Ozamiz', 'Del Pilar Street', 'Ozamiz City', 'Misamis Occidental', 8.1470175, 123.8459793],
        ['Payapa Roxas', 'Del Pilar Street', 'Roxas City', 'Capiz', 11.5831593, 122.7525555],
        ['Ligaya Dumaguete', 'Rizal Street', 'Dumaguete City', 'Negros Oriental', 9.3055063, 123.3082522],
        ['Ligaya Tacloban', 'Mabini Street', 'Tacloban City', 'Leyte', 11.2431609, 125.0082936],
        ['Malaya Malolos', 'Mabini Street', 'Malolos City', 'Bulacan', 14.8526836, 120.8160252],
        ['Malaya San Jose Del Monte', 'Osmena Boulevard', 'San Jose Del Monte', 'Bulacan', 14.8101978, 121.0474088],
        ['Ginhawa Antipolo', 'Osmena Boulevard', 'Antipolo City', 'Rizal', 14.5871972, 121.1759246],
        ['Lakbay Bacoor', 'Roxas Avenue', 'Bacoor City', 'Cavite', 14.4593497, 120.9401912],
        ['Lakbay Imus', 'Del Pilar Street', 'Imus City', 'Cavite', 14.4290216, 120.9365838],
        ['Tahimik Dasmarinas', 'Del Pilar Street', 'Dasmarinas City', 'Cavite', 14.3435028, 120.9484977],
        ['Tahimik San Pedro', 'Quezon Avenue', 'San Pedro City', 'Laguna', 14.3384729, 121.0312829],
        ['Tahimik Binan', 'Bonifacio Street', 'Binan City', 'Laguna', 14.3388196, 121.0778089],
        ['Kapwa Santa Rosa', 'Mabini Street', 'Santa Rosa City', 'Laguna', 14.3146042, 121.1137004],
        ['Bahaghari Lipa', 'Bonifacio Street', 'Lipa City', 'Batangas', 13.9414340, 121.1642826],
        ['Bahaghari Batangas City', 'Roxas Avenue', 'Batangas City', 'Batangas', 13.7552594, 121.0590753],
        ['Alagang Lucena', 'Roxas Avenue', 'Lucena City', 'Quezon', 13.9357696, 121.6128612],
        ['Malinis Puerto Princesa', 'Magsaysay Street', 'Puerto Princesa City', 'Palawan', 9.7398561, 118.7438187],
        ['Ligtas Kalibo', 'Del Pilar Street', 'Kalibo', 'Aklan', 11.7088966, 122.3640225],
        ['Ligtas Ormoc', 'Quezon Avenue', 'Ormoc City', 'Leyte', 11.0052622, 124.6090638],
        ['Ligtas Bislig', 'Bonifacio Street', 'Bislig City', 'Surigao del Sur', 8.2130815, 126.3156173],
        ['Damayan Surigao', 'Mabini Street', 'Surigao City', 'Surigao del Norte', 9.7905028, 125.4935697],
        ['Damayan Cotabato City', 'Osmena Boulevard', 'Cotabato City', 'Maguindanao', 7.2237628, 124.2467062],
        ['Tibay Marawi', 'Osmena Boulevard', 'Marawi City', 'Lanao del Sur', 8.0047262, 124.2854351],
        ['Sinag Pagadian', 'Roxas Avenue', 'Pagadian City', 'Zamboanga del Sur', 7.8249717, 123.4365816],
        ['Sinag Tuguegarao', 'Del Pilar Street', 'Tuguegarao City', 'Cagayan', 17.6118858, 121.7300377],
        ['Ganda Ilagan', 'Del Pilar Street', 'Ilagan City', 'Isabela', 17.1486341, 121.8886466],
        ['Bayan Vigan', 'Rizal Street', 'Vigan City', 'Ilocos Sur', 17.5751881, 120.3879038],
        ['Pag-asa Laoag', 'Quezon Avenue', 'Laoag City', 'Ilocos Norte', 18.1954482, 120.5926755],
        ['Pag-asa Dagupan', 'Bonifacio Street', 'Dagupan City', 'Pangasinan', 16.0430210, 120.3337627],
        ['Sigla San Fernando', 'Bonifacio Street', 'San Fernando City', 'Pampanga', 15.0691071, 120.6528206],
        ['Sigla Tarlac', 'Roxas Avenue', 'Tarlac City', 'Tarlac', 15.4861218, 120.5893473],
        ['Sigla Cabanatuan', 'Del Pilar Street', 'Cabanatuan City', 'Nueva Ecija', 15.4905045, 120.9684264],
        ['Tatag Olongapo', 'Magsaysay Street', 'Olongapo City', 'Zambales', 14.8388848, 120.2843587],
        ['Kalusugan Alaminos', 'Del Pilar Street', 'Alaminos City', 'Pangasinan', 16.1553857, 119.9792201],
        ['Kalusugan Urdaneta', 'Quezon Avenue', 'Urdaneta City', 'Pangasinan', 15.9759995, 120.5668992],
        ['Liwanag Malaybalay', 'Quezon Avenue', 'Malaybalay City', 'Bukidnon', 8.1550421, 125.1305726],
        ['Bukas Valencia', 'Mabini Street', 'Valencia City', 'Bukidnon', 7.9066812, 125.0910548],
        ['Bukas Tandag', 'Osmena Boulevard', 'Tandag City', 'Surigao del Sur', 9.0799833, 126.1974606],
        ['Sagip Cabuyao', 'Magsaysay Street', 'Cabuyao City', 'Laguna', 14.2414037, 121.1565601],
        ['Tanglaw Meycauayan', 'Rizal Street', 'Meycauayan City', 'Bulacan', 14.7345008, 120.9571635],
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
        $ownerPermissions = RoleEnum::BranchManager->permissions();
        $adminId = PlatformAdmin::query()->value('user_id');

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
                $adminId,
                &$branchIndex
            ) {
                $planType = $spec['branches'] > Plan::branchLimitFor(Plan::TYPE_SME)
                    ? Plan::TYPE_ENTERPRISE
                    : Plan::TYPE_SME;
                $plan = $plans["{$spec['plan']}-{$planType}"];
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

                [, $agencyStreet, $agencyCity, $agencyProvince, $agencyLatitude, $agencyLongitude] = self::BRANCHES[$branchIndex];

                $agencyLocation = Location::create([
                    'street' => $agencyStreet,
                    'city' => $agencyCity,
                    'province' => $agencyProvince,
                    'country' => 'Philippines',
                    'latitude' => $agencyLatitude,
                    'longitude' => $agencyLongitude,
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
                    'mode' => Subscription::MODE_LIVE,
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
                    'masked_card_number' => '400000XXXXXX' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
                    'price' => (float) $plan->price,
                    'status' => $isRejected
                        ? SubscriptionPayment::STATUS_REFUNDED
                        : SubscriptionPayment::STATUS_PAID,
                    'type' => SubscriptionPayment::TYPE_SUBSCRIPTION,
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
                        [$name, $street, $city, $province, $latitude, $longitude] = self::AMUMA_EXTRA_BRANCHES[$n - 1];
                    } else {
                        [$name, $street, $city, $province, $latitude, $longitude] = self::BRANCHES[$branchIndex];
                    }

                    $location = Location::create([
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

                    $link = BranchSubscription::create([
                        'subscription_id' => $subscription->subscription_id,
                        'branch_id' => $branch->branch_id,
                        'status' => match ($effectiveStatus) {
                            'pending' => BranchSubscription::STATUS_PENDING,
                            'rejected' => BranchSubscription::STATUS_REJECTED,
                            default => BranchSubscription::STATUS_APPROVED,
                        },
                    ]);

                    $includesAgency = $n === 0 && ($isRejected || $effectiveStatus === 'verified');
                    $scope = $includesAgency ? VerificationLog::SCOPE_BOTH : VerificationLog::SCOPE_BRANCH;
                    $reasons = $includesAgency ? self::AGENCY_REJECTION_REASONS : self::BRANCH_REJECTION_REASONS;
                    $reason = $reasons[$cosmeticIndex % count($reasons)];

                    if ($effectiveStatus === 'verified' && $n > 0 && $cosmeticIndex % 3 === 0) {
                        $this->logDecision(
                            $link,
                            VerificationLog::ACTION_REJECTED,
                            VerificationLog::SCOPE_BRANCH,
                            self::BRANCH_REJECTION_REASONS[$cosmeticIndex % count(self::BRANCH_REJECTION_REASONS)],
                            $adminId,
                            $start->copy()->addHours(3 + $n),
                        );
                    }

                    if ($effectiveStatus !== 'pending') {
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
            'minimum_adl_hours' => (string) [8, 6, 4][$index % 3],
            'tin' => sprintf('%03d-%03d-%03d-%03d', 100 + $index, 200 + $index, 300 + $index, 1),
            'is_open' => '1',
        ];
    }
}
