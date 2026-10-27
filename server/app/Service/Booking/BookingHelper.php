<?php

namespace App\Service\Booking;

use App\Models\Booking;
use App\Models\BranchContract;
use App\Repository\BranchContractRepository;
use App\Repository\ServiceRepository;
use App\Rules\NoEmoji;
use App\Service\External\SupabaseService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;

class BookingHelper
{
    public const DIAGNOSIS_WINDOW_MONTHS = 3;

    public const HOMECARE_LEAD_MINUTES = 60;

    public function __construct(
        private BranchContractRepository $branchContractRepository,
        private ServiceRepository $serviceRepository
    ) {}

    public  function getTotal(array $payload)
    {
        return match (strtolower($payload['category'])) {
            'homecare' => match ($payload['booking_data']['homecare']['type']) {
                'Medical' => $this->getMedicalTotal($payload),
                'ADL'     => $this->getAdlTotal($payload, $payload['branch_id']),
                default   => 0,
            },
            'facility' => $this->getFacilityTotal($payload,  $payload['branch_id']),
            default    => 0,
        };
    }

    protected function getMedicalTotal(array $payload)
    {
        $serviceIds = collect($payload['booking_data']['homecare']['services'])
            ->pluck('service_id')
            ->toArray();

        $services = $this->serviceRepository->findByFields([
            ['service_id', 'IN', $serviceIds],
        ]);

        return $services->sum('price');
    }

    protected function getAdlTotal(array $payload, string $branchId)
    {

        $contract = $this->branchContractRepository->findByField([
            ['branch_id', '=', $branchId],
            ['accommodation_type', '=', strtoupper($payload['booking_data']['homecare']['type'])],
            ['billing_cycle', '=', BranchContract::BILLING_CYCLE_HOURLY],
            ['is_active', '=', true],
        ]);

        if (!$contract) {
            throw new Exception('No active ADL pricing contract is configured for this branch.', 404);
        }

        $total = $payload['booking_data']['homecare']['time_span'] * $contract->price;

        if ($total <= 0) {
            throw new Exception('The ADL pricing contract has an invalid price.', 422);
        }

        return [
            'total' => $total,
            'price' => $contract['price']
        ];
    }

    protected function getFacilityTotal(array $payload, string $branchId)
    {

        $contract = $this->branchContractRepository->findByField([
            ['branch_id', '=', $branchId],
            ['accommodation_type', '=', strtoupper($payload['booking_data']['facility']['plan'])],
            ['billing_cycle', '=', strtoupper($payload['booking_data']['facility']['billing_cycle'])],
            ['is_active', '=', true],
        ]);

        if (!$contract) {
            throw new Exception(
                sprintf(
                    'No active %s (%s) pricing contract is configured for this branch.',
                    $payload['booking_data']['facility']['plan'],
                    $payload['booking_data']['facility']['billing_cycle']
                ),
                404
            );
        }

        if ($contract->price <= 0) {
            throw new Exception(
                sprintf(
                    'The %s (%s) pricing contract has an invalid price.',
                    $contract->accommodation_type,
                    $contract->billing_cycle
                ),
                422
            );
        }

        return $contract->price;
    }

    public function resolvePayment(array $payload)
    {
        $isFreePreAdmission = ($payload['category'] ?? null) === 'facility'
            && ($payload['booking_data']['facility']['type'] ?? null) === 'Pre-Admission';

        if ($isFreePreAdmission) {
            return ['total_amount' => 0, 'paid' => false];
        }

        $total = $this->getTotal($payload);

        return [
            'total_amount' => is_array($total) ? ($total['total'] ?? 0) : ($total ?? 0),
            'payment_status' => 'pending'
        ];
    }

    public function isCompleteAdmission(array $payload): bool
    {
        return ($payload['category'] ?? null) === 'facility'
            && ($payload['booking_data']['facility']['type'] ?? null) === 'Complete';
    }

    public function isHomecare(array $payload): bool
    {
        return ($payload['category'] ?? null) === Booking::CATEGORY_ONLINE;
    }

    public function assertHomecareLeadTime(array $homecare, mixed $branch): void
    {
        $timezone = ($branch?->settings ?? [])['time_zone'] ?? config('app.timezone');

        $scheduled = Carbon::parse(
            ($homecare['date'] ?? '') . ' ' . ($homecare['prefered_time'] ?? ''),
            $timezone
        );

        $earliest = Carbon::now($timezone)
            ->startOfMinute()
            ->addMinutes(self::HOMECARE_LEAD_MINUTES);

        if ($scheduled->lt($earliest)) {
            throw new Exception('Homecare must be booked at least 1 hour ahead. Please choose a later time.', 422);
        }
    }

    public function requiresPayment(array $payload): bool
    {
        return $this->isCompleteAdmission($payload) || $this->isHomecare($payload);
    }

    public function getBookingPercent(array $payload): int
    {
        $branch = $payload['branch'] ?? null;
        $settings = $branch?->settings ?? [];

        if ($this->isCompleteAdmission($payload)) {
            return min(100, max(1, (int) ($settings['complete_admission_booking_percent'] ?? 100)));
        }

        if ($this->isHomecare($payload)) {
            return min(100, max(1, (int) ($settings['homecare_downpayment_percent'] ?? 50)));
        }

        return 100;
    }

    // What a family can still pay online on a homecare booking nobody has paid
    // for yet: the branch's downpayment percent of the total.
    public function portalPaymentDue(Booking $booking): ?array
    {
        $data = $booking->booking_data ?? [];
        $payment = $data['payment'] ?? [];
        $total = round((float) ($payment['total_amount'] ?? 0), 2);

        if (
            $booking->category !== Booking::CATEGORY_ONLINE
            || $booking->status !== Booking::STATUS_PENDING
            || ($payment['payment_status'] ?? null) !== 'pending'
            || $total <= 0
        ) {
            return null;
        }

        $percent = $this->getBookingPercent([
            'category' => $booking->category,
            'branch' => $booking->branch,
            'booking_data' => $data,
        ]);

        return [
            'percent' => $percent,
            'total' => $total,
            'amount' => round($total * $percent / 100, 2),
        ];
    }

    public function resolveBookingPayment(array $payload): array
    {
        $total = $this->getTotal($payload);
        $totalAmount = (float) (is_array($total) ? ($total['total'] ?? 0) : ($total ?? 0));

        if ($totalAmount <= 0) {
            throw new Exception('The booking amount could not be determined.', 422);
        }

        $percent = $this->getBookingPercent($payload);
        $bookingAmount = round($totalAmount * $percent / 100, 2);

        return [
            'total_amount' => $totalAmount,
            'booking_percent' => $percent,
            'booking_amount' => $bookingAmount,
            'balance_amount' => round($totalAmount - $bookingAmount, 2),
        ];
    }

    public function resolvePayment2(array $payload)
    {
        $contract = $this->branchContractRepository->findByField([
            ['branch_id', '=', $payload['branch_id']],
            ['branch_contract_id', '=', $payload['reserved']['contract_id']],
            ['is_active', '=', true],
        ]);

        if (!$contract) {
            throw new Exception(
                sprintf(
                    'No active %s (%s) pricing contract is configured for this branch.',
                    $payload['facility']['plan'] ??  $payload['reserved']['accommodation_plan'],
                    $payload['facility']['billing_cycle'] ?? $payload['reserved']['billing_cycle']
                ),
                404
            );
        }

        if ($contract->price <= 0) {
            throw new Exception(
                sprintf(
                    'The %s (%s) pricing contract has an invalid price.',
                    $contract->accommodation_type,
                    $contract->billing_cycle
                ),
                422
            );
        }

        return [
            'total_amount' => $contract->price ?? 0,
            'paid' => false,
        ];
    }

    public function resolveAssessment(array $assessment)
    {
        return $assessment;
    }

    public function resolvePatient(mixed $patient): array
    {
        $patient = is_array($patient) ? $patient : [];
        $avatar = $patient['avatar'] ?? null;

        if ($avatar instanceof UploadedFile) {
            try {
                $patient['avatar'] = SupabaseService::store($avatar)['url'] ?? null;
            } catch (\Throwable $e) {
                throw new Exception('We couldn\'t upload the patient photo. Please try again or use a different image.', 422);
            }
        } elseif (!is_string($avatar) || !filter_var($avatar, FILTER_VALIDATE_URL)) {
            unset($patient['avatar']);
        }

        return $patient;
    }

    public function assertNoEmoji(array $data): void
    {
        $fields = [
            'patient' => $data['patient'] ?? [],
            'guardian' => $data['guardian'] ?? [],
            'diagnoses' => $data['diagnoses'] ?? [],
        ];

        $rules = [
            'patient.*' => [new NoEmoji()],
            'guardian.*' => [new NoEmoji()],
            'diagnoses.*.diagnosis' => [new NoEmoji()],
            'diagnoses.*.diagnosis_notes' => [new NoEmoji()],
        ];

        validator($fields, $rules, [], [
            'patient.*' => 'patient details',
            'guardian.*' => 'guardian details',
            'diagnoses.*.diagnosis' => 'diagnosis',
            'diagnoses.*.diagnosis_notes' => 'diagnosis notes',
        ])->validate();
    }

    public function assertDiagnoses(mixed $diagnoses): void
    {
        $windowStart = now()->subMonths(self::DIAGNOSIS_WINDOW_MONTHS)->toDateString();

        $started = collect(is_array($diagnoses) ? $diagnoses : [])
            ->filter(fn($entry) => is_array($entry) && collect([
                'diagnosis',
                'diagnosis_date',
                'diagnosis_notes',
                'diagnosis_file',
            ])->contains(fn($key) => filled($entry[$key] ?? null)))
            ->values()
            ->all();

        validator(
            ['diagnoses' => $started],
            [
                'diagnoses' => ['nullable', 'array'],
                'diagnoses.*.diagnosis' => ['required', 'string', 'max:200'],
                'diagnoses.*.diagnosis_date' => [
                    'required',
                    'date',
                    'before_or_equal:today',
                    'after_or_equal:' . $windowStart,
                ],
            ],
            [
                'diagnoses.*.diagnosis.required' => 'Primary Diagnosis is required.',
                'diagnoses.*.diagnosis_date.required' => 'Date Diagnosed is required.',
                'diagnoses.*.diagnosis_date.before_or_equal' => 'Diagnosis date cannot be in the future.',
                'diagnoses.*.diagnosis_date.after_or_equal' => 'Diagnosis must be from the past ' . self::DIAGNOSIS_WINDOW_MONTHS . ' months.',
            ],
        )->validate();
    }

    public function resolveDiagnoses(mixed $diagnoses): array
    {
        if (!is_array($diagnoses)) {
            return [];
        }

        return array_values(array_map(
            fn($diagnosis) => $this->resolveDiagnosis((array) $diagnosis),
            $diagnoses
        ));
    }

    public function resolveDiagnosis(array $diagnosis)
    {
        if (empty($diagnosis['diagnosis_file'])) {
            return $diagnosis;
        }

        try {
            $uploadResult = SupabaseService::store($diagnosis['diagnosis_file']);

            if (empty($uploadResult['url'])) {
                throw new Exception('Diagnosis file upload failed: no URL returned.');
            }

            $diagnosis['diagnosis_file'] = $uploadResult['url'];
        } catch (\Throwable $e) {
            throw new Exception('We couldn\'t upload your diagnosis file. Please try again or use a different file.', 422);
        }

        return $diagnosis;
    }
}
