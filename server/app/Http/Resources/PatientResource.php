<?php

namespace App\Http\Resources;

use App\Models\AdmissionPeriod;
use App\Models\Schedule;
use App\Models\ScheduleService;
use App\Utils\DischargeCalculator;
use App\Utils\OutstandingBalance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    private function wantsSection(Request $request, string $section): bool
    {
        $sections = $request->query('sections');

        if (!$sections) {
            return true;
        }

        if (is_string($sections)) {
            $sections = array_filter(explode(',', $sections));
        }

        $sections = array_map('trim', (array) $sections);

        return in_array('all', $sections, true)
            || in_array($section, $sections, true);
    }

    public function toArray(Request $request)
    {
        return [
            'patient_id' => $this->patient_id,
            'uuid' => $this->uuid,
            'patient_code' => $this->patient_code,
            'full_name' => trim("{$this->first_name} {$this->middle_name} {$this->last_name}"),
            'first_name' => $this->first_name,
            'avatar' => $this->avatar_url,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth,
            'age' => $this->date_of_birth?->age,
            'blood_type' => $this->blood_type,
            'height' => $this->height,
            'weight' => $this->weight,
            'phone_number' => $this->phone_number,
            'citizenship' => $this->citizenship,
            'allergies' => $this->allergies ?? [],

            'has_homecare' => $this->relationLoaded('schedules')
                ? $this->schedules->contains(
                    fn($schedule) => $schedule->category === Schedule::CATEGORYHOMECARE
                )
                : false,

            'location' => $this->whenLoaded('location', fn() => [
                'full_address' => $this->location?->full_address,
            ]),


            'schedules' => $this->whenLoaded('schedules', fn() => $this->schedules
                ->sortBy(fn($schedule) => [
                    match ($schedule->status) {
                        Schedule::STATUS_ONGOING => 0,
                        Schedule::STATUS_PENDING => 1,
                        default => 2,
                    },
                    -strtotime((string) $schedule->scheduled_at),
                ])
                ->map(fn($schedule) => [
                    'uuid' => $schedule->uuid,
                    'schedule_code' => $schedule->schedule_code,
                    'status' => $schedule->status,
                    'category' => $schedule->category,
                    'scheduled_at' => $schedule->scheduled_at,
                    'address' => $schedule->relationLoaded('location')
                        ? $schedule->location?->full_address
                        : null,
                    'latitude' => $schedule->relationLoaded('location')
                        ? $schedule->location?->latitude
                        : null,
                    'longitude' => $schedule->relationLoaded('location')
                        ? $schedule->location?->longitude
                        : null,
                    'type' => $schedule->relationLoaded('scheduleServices')
                        && $schedule->scheduleServices->contains(
                            fn($service) => $service->type === ScheduleService::TYPE_ADL
                        )
                        ? ScheduleService::TYPE_ADL
                        : ScheduleService::TYPE_MEDICAL,
                ])
                ->values()),

            'assessment' => $this->whenLoaded('assessments', fn() => $this->assessments
                ->map(fn($assessment) => [
                    'uuid' => $assessment->uuid,
                    'condition' => $assessment->condition,
                    'mental_state' => $assessment->mental_state,
                    'affect' => $assessment->affect,
                    'behavior' => $assessment->behavior,
                    'communication' => $assessment->communication,
                    'speech' => $assessment->speech,
                    'life_system_profile' => $assessment->life_system_profile,
                ])
                ->values()),

            'diagnoses' => $this->whenLoaded('diagnoses', fn() => $this->diagnoses
                ->map(fn($diagnosis) => [
                    'uuid' => $diagnosis->uuid,
                    'diagnosis' => $diagnosis->diagnosis,
                    'diagnosis_date' => $diagnosis->diagnosis_date?->toDateString(),
                    'diagnosis_notes' => $diagnosis->diagnosis_notes,
                    'diagnosis_file' => $diagnosis->diagnosis_file,
                ])
                ->values()),


            'family' => $this->whenLoaded('patientAccess', function () {
                $ordered = $this->patientAccess->sortBy('patient_access_id');

                $primaryId = $ordered
                    ->firstWhere('have_access', true)
                    ?->patient_access_id;

                return $ordered
                    ->map(fn($access) => [
                        'patient_access_id' => $access->patient_access_id,
                        'relationship_type' => $access->relationship_type,
                        'have_access' => (bool) $access->have_access,
                        'is_primary' => $access->patient_access_id === $primaryId,
                        'client' => $access->client ? [
                            'client_id' => $access->client->client_id,
                            'full_name' => trim(
                                ($access->client->first_name ?? '') . ' ' .
                                    ($access->client->last_name ?? '')
                            ) ?: null,
                            'phone_number' => $access->client->phone_number,
                            'email' => $access->client->user?->email,
                            'occupation' => $access->client->occupation,
                            'avatar' => $access->client->avatar,
                        ] : null,
                    ])
                    ->values();
            }),

            'medications_count' => $this->medications_count ?? 0,
            'vitals_count' => $this->vitals_count ?? 0,

            'billing' => $this->when(
                $this->wantsSection($request, 'billing'),
                fn() => $this->billing_summary
            ),

            'admissions' => $this->whenLoaded('admissions', function () {
                return $this->admissions
                    ->sortByDesc('created_at')
                    ->map(fn($admission) => $this->formatAdmission($admission))
                    ->values();
            }),

            'current_admission' => $this->whenLoaded('currentAdmission', function () {
                $admission = $this->loadedAdmission($this->currentAdmission);

                return $admission
                    ? $this->formatAdmission($admission, true)
                    : null;
            }),

            'latest_admission' => $this->whenLoaded('latestAdmission', function () {
                $admission = $this->loadedAdmission($this->latestAdmission);

                return $admission ? $this->formatAdmission($admission) : null;
            }),
        ];
    }

    private function loadedAdmission(mixed $admission)
    {
        if (!$admission || !$this->resource->relationLoaded('admissions')) {
            return $admission;
        }

        return $this->admissions->firstWhere(
            'patient_admission_id',
            $admission->patient_admission_id
        ) ?? $admission;
    }

    private function formatAdmission(mixed $admission, bool $includeDischargeCalculation = false)
    {
        $currentInvoice = $admission->relationLoaded('currentInvoiceAdmission')
            ? $admission->currentInvoiceAdmission
            : null;

        $invoice = $currentInvoice
            ?? ($admission->relationLoaded('invoiceAdmission')
                ? $admission->invoiceAdmission->sortByDesc('created_at')->first()
                : null);

        $period = ($admission->relationLoaded('currentPeriod') ? $admission->currentPeriod : null)
            ?? ($admission->relationLoaded('latestPeriod') ? $admission->latestPeriod : null);

        $contract = $period?->branchContract;

        $bed = $admission->relationLoaded('bed') ? $admission->bed : null;
        $room = $bed?->relationLoaded('room') ? $bed->room : null;

        return [
            'patient_admission_id' => $admission->patient_admission_id,
            'caregiver_count' => \App\Models\CaregiverFacilityShift::where('admission_id', $admission->patient_admission_id)
                ->where('is_active', true)
                ->count(),
            'status' => $admission->status,
            'admitted_at' => $admission->admitted_at,
            'end_date' => $admission->end_date,
            'note' => $admission->note,

            'bed' => [
                'bed_id' => $bed?->bed_id,
                'bed_no' => $bed?->bed_no ?? 'N/A',
                'status' => $bed?->status,
            ],

            'room' => [
                'room_id' => $room?->room_id,
                'room_no' => $room?->room_no ?? 'N/A',
                'room_type' => $room?->room_type,
                'floor' => $room?->floor,
            ],

            'current_contract' => $this->formatContract($contract),

            'current_period' => $this->formatPeriod($period),

            'future_periods' => $this->formatFuturePeriods($admission, $period),

            'current_invoice' => $this->formatInvoiceAdmission($currentInvoice),

            'discharge_calculation' => ($includeDischargeCalculation && $invoice && $period && $contract)
                ? DischargeCalculator::getDischargeCalculation(
                    $invoice->invoice,
                    $admission,
                    $period
                ) + [
                    'outstanding' => OutstandingBalance::forInvoices(
                        $this->patient_invoices,
                        $invoice->invoice,
                        ($this->futurePeriods($admission, $period) ?? collect())
                            ->pluck('admission_period_id')
                            ->all(),
                        $admission->patient_admission_id
                    ),
                ]
                : null,

            'invoices' => $admission->relationLoaded('invoiceAdmission')
                ? $admission->invoiceAdmission
                ->map(fn($invoice) => $this->formatInvoiceAdmission($invoice))
                ->values()
                : [],
        ];
    }

    private function futurePeriods(mixed $admission, mixed $current)
    {
        if (!$admission->relationLoaded('periods')) {
            return null;
        }

        return $admission->periods
            ->whereNotIn('status', AdmissionPeriod::CLOSED_STATUSES)
            ->when(
                $current,
                fn($periods) => $periods->where(
                    'admission_period_id',
                    '!=',
                    $current->admission_period_id
                )
            );
    }

    private function formatFuturePeriods(mixed $admission, mixed $current)
    {
        $future = $this->futurePeriods($admission, $current);

        if ($future === null) {
            return null;
        }

        $lines = $future->flatMap(fn($period) => $period->invoiceAdmissionLines);

        return [
            'count' => $future->count(),
            'charged_amount' => round((float) $lines->sum('price'), 2),
            'groups' => $future
                ->groupBy(fn($period) => $period->branchContract?->billing_cycle ?? '')
                ->map(fn($periods, $cycle) => [
                    'billing_cycle' => $cycle ?: null,
                    'count' => $periods->count(),
                    'charged_amount' => round(
                        (float) $periods
                            ->flatMap(fn($period) => $period->invoiceAdmissionLines)
                            ->sum('price'),
                        2
                    ),
                ])
                ->values(),
            'invoices' => $lines
                ->map(fn($line) => $this->formatInvoiceAdmission($line))
                ->filter()
                ->values(),
        ];
    }

    private function formatPeriod(mixed $period): ?array
    {
        if (!$period) {
            return null;
        }

        $charged = $period->invoiceAdmissionLines->sum('price');

        return [
            'admission_period_id' => $period->admission_period_id,
            'status' => $period->status,
            'reason' => $period->reason,
            'note' => $period->note,
            'started_at' => $period->start_date,
            'ended_at' => $period->end_date,
            'charged_amount' => round((float) $charged, 2),
            'contract' => $this->formatContract($period->branchContract),
        ];
    }

    private function formatInvoiceAdmission(mixed $invoiceAdmissionLines): ?array
    {
        if (!$invoiceAdmissionLines) {
            return null;
        }

        $invoice = $invoiceAdmissionLines->invoice;

        return [
            'invoice_admission_id' => $invoiceAdmissionLines->invoice_admission_id,
            'invoice_id' => $invoiceAdmissionLines->invoice_id,
            'invoice_code' => $invoice?->invoice_code,
            'status' => $invoice?->status,
            'price' => round((float) $invoiceAdmissionLines->price, 2),
            'description' => $invoiceAdmissionLines->description,

            'paid_amount' => $invoice?->amount_paid ?? 0,
            'refunded_amount' => $invoice?->refunded_amount ?? 0,
            'net_paid_amount' => $invoice?->net_paid_amount ?? 0,
            'refund_status' => $invoice?->refund_status ?? 'none',

            'admission_period_id' => $invoiceAdmissionLines->admission_period_id,
            'period_code' => AdmissionPeriod::codeFor($invoiceAdmissionLines->admission_period_id),
            'parent_admission_period_id' => $invoiceAdmissionLines->admissionPeriod?->parent_admission_period_id,
            'accommodation_status' => $invoiceAdmissionLines->status,
            'accommodation_reason' => $invoiceAdmissionLines->admissionPeriod?->reason,
            'period_start' => $invoiceAdmissionLines->admissionPeriod?->start_date,
            'period_end' => $invoiceAdmissionLines->admissionPeriod?->end_date,
            'moved_at' => $invoiceAdmissionLines->admissionPeriod?->created_at,
            'contract' => $this->formatContract($invoiceAdmissionLines->branchContract),
        ];
    }

    private function formatContract(mixed $contract): ?array
    {
        if (!$contract) {
            return null;
        }

        return [
            'branch_contract_id' => $contract->branch_contract_id,
            'category' => $contract->category,
            'accommodation_type' => $contract->accommodation_type,
            'billing_cycle' => $contract->billing_cycle,
            'price' => $contract->price,
        ];
    }
}
