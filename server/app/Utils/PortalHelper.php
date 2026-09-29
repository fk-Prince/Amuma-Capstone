<?php

namespace App\Utils;

use App\Http\Resources\CaregiverShiftResource;
use App\Models\AdmissionPeriod;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\PatientAccess;
use App\Models\Schedule;
use App\Models\ScheduleService;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PortalHelper
{

    public function bookingPayload(Booking $booking): array
    {
        $data = is_string($booking->booking_data)
            ? json_decode($booking->booking_data, true)
            : $booking->booking_data;
        $data ??= [];

        $payment = $data['payment'] ?? null;

        return [
            'booking_id' => $booking->booking_id,
            'reference_id' => $booking->reference_id,
            'category' => $booking->category,
            'booking_type' => $booking->booking_type,
            'valid_until' => $booking->valid_until,
            'status' => $booking->status,
            'reason' => $booking->reason,
            'reviewed_by' => $booking->reviewed_by_name,
            'reviewed_at' => $booking->reviewed_by ? $booking->updated_at : null,
            'branch_name' => $booking->branch?->name,
            'branch_image' => $booking->branch?->image,

            'facility' => $data['facility'] ?? null,
            'homecare' => $data['homecare'] ?? null,

            'patient' => [
                'patient_id' => $data['patient']['patient_id'] ?? null,
                'first_name' => $data['patient']['first_name'] ?? null,
                'middle_name' => $data['patient']['middle_name'] ?? null,
                'last_name' => $data['patient']['last_name'] ?? null,
                'gender' => $data['patient']['gender'] ?? null,
                'citizenship' => $data['patient']['citizenship'] ?? null,
                'occupation' => $data['patient']['occupation'] ?? null,
                'date_of_birth' => $data['patient']['date_of_birth'] ?? null,
                'phone_number' => $data['patient']['phone_number'] ?? null,
                'marital_status' => $data['patient']['marital_status'] ?? null,
                'height' => $data['patient']['height'] ?? null,
                'weight' => $data['patient']['weight'] ?? null,
                'blood_type' => $data['patient']['blood_type'] ?? null,
                'address' => $data['patient']['address'] ?? null,
            ],

            'guardian' => [
                'first_name' => $data['guardian']['first_name'] ?? null,
                'middle_name' => $data['guardian']['middle_name'] ?? null,
                'last_name' => $data['guardian']['last_name'] ?? null,
                'phone_number' => $data['guardian']['phone_number'] ?? null,
                'email' => $data['guardian']['email'] ?? null,
                'relationship' => $data['guardian']['relationship'] ?? null,
                'occupation' => $data['guardian']['occupation'] ?? null,
                'address' => $data['guardian']['address'] ?? null,
            ],

            'assessment' => $data['assessment'] ?? null,
            'diagnoses' => $data['diagnoses'] ?? [],
            'reserved' => $data['reserved'] ?? null,


            'payment' => !empty($payment['payment_status']) ? $payment : null,

            'created_at' => $booking->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $booking->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    public static function scheduleDetailRelations(): array
    {
        return [
            'location',
            'scheduleServices.service',
            'scheduleServices.invoiceServices',
            'scheduleServices.assigned' => fn($query) =>
            $query->with([
                'employee.employees.employeeBranch',
                'onlineSchedules',
            ]),
        ];
    }

    public static function currentHomecareSchedules(object $patient): Collection
    {
        if (!$patient->relationLoaded('schedules')) {
            return collect();
        }

        $homecare = $patient->schedules
            ->filter(fn($schedule) => $schedule->category === Schedule::CATEGORYHOMECARE)
            ->values();

        $admission = $patient->latestAdmission;

        if ($homecare->isEmpty() || !$admission) {
            return $homecare;
        }

        $admissionStart = $admission->admitted_at ?? $admission->created_at;

        return $homecare->contains(fn($schedule) => $schedule->scheduled_at?->gte($admissionStart))
            ? $homecare
            : collect();
    }

    public function pickSchedules(Collection $schedules): array
    {
        return [
            'adl' => $schedules->first(
                fn($schedule) => $schedule->scheduleServices->contains(
                    fn($service) => $service->type === ScheduleService::TYPE_ADL
                )
            ),
            'medical' => $schedules->first(
                fn($schedule) => $schedule->scheduleServices->contains(
                    fn($service) => $service->type !== ScheduleService::TYPE_ADL
                        && $service->service_id !== null
                )
            ),
        ];
    }

    public function patientPayload(PatientAccess $access, object $patient, array $sections = ['all'])
    {
        $wants = fn(string $section) => in_array('all', $sections, true) || in_array($section, $sections, true);

        $payload = [
            'patient_id' => $patient->patient_id,
        ];

        if ($wants('profile')) {
            $payload['access'] = self::access($access);
            $payload['patient'] = self::patient($patient);
            $payload['organization'] = self::organization($patient);
            $payload['location_context'] = self::locationContext($patient);
        }

        if ($wants('details')) {
            $payload['patient'] = ($payload['patient'] ?? ['patient_id' => $patient->patient_id])
                + self::patientDetails($patient);
            $payload['client'] = self::client($access->client);
        }

        if ($wants('medication') || in_array('recent_medication', $sections, true)) {
            $payload['patient'] = ($payload['patient'] ?? ['patient_id' => $patient->patient_id])
                + ['medication' => self::medication($patient)];
        }

        if ($wants('financials')) {
            $invoices = $patient->getRelation('invoices');
            $active = self::invoices($invoices);

            $payload += self::balances($invoices, true);
            $payload['latest_invoice'] = $active[0] ?? null;
            $payload['invoices'] = $active;
            $payload['voided_invoices'] = self::invoices($invoices, true);
            $payload['transactions'] = self::transactions($invoices);
        } elseif (in_array('summary', $sections, true)) {
            $invoices = $patient->getRelation('invoices');
            $latest = $invoices->first(fn($invoice) => $invoice->status !== Invoice::STATUS_VOID);

            $payload += self::balances($invoices);
            $payload['latest_invoice'] = $latest ? [
                'invoice_id' => $latest->invoice_id,
                'invoice_code' => $latest->invoice_code,
                'status' => $latest->status,
                'balance_due' => (float) $latest->balance_due,
            ] : null;
            $payload['transactions'] = self::transactions($invoices);
        }

        if ($wants('schedule')) {
            $payload['schedule'] = self::scheduleContext($patient);
        }

        if ($wants('activity')) {
            $payload['activities'] = $patient->activities
                ->map(fn($activity) => PatientActivityPresenter::patientActivity($activity))
                ->sortByDesc('occurredAt')
                ->values();
        }

        if ($wants('admissions')) {
            $payload['admissions'] = self::admissionTimeline($patient);
        }

        if ($wants('caregiver_shifts')) {
            $payload['caregiver_shifts'] = self::caregiverShifts($patient);
        }

        return $payload;
    }

    private function caregiverShifts(object $patient): array
    {
        $admission = $patient->currentAdmission;

        if (!$admission) {
            return [];
        }

        return CaregiverShiftResource::collection($admission->caregiverShifts)
            ->resolve();
    }

    // The portal timeline only ever shows the current admission, so this is a
    // trimmed version of PatientResource::formatAdmission() without the
    // dashboard-only discharge calculation.
    private function admissionTimeline(object $patient): array
    {
        $admission = $patient->currentAdmission;

        if (!$admission) {
            return [];
        }

        $period = $admission->currentPeriod ?? $admission->latestPeriod;

        return [[
            'patient_admission_id' => $admission->patient_admission_id,
            'status' => $admission->status,
            'admitted_at' => $admission->admitted_at?->format('Y-m-d H:i:s'),
            'end_date' => $admission->end_date?->format('Y-m-d H:i:s'),

            'current_contract' => self::contract($period?->branchContract),

            'current_period' => $period ? [
                'admission_period_id' => $period->admission_period_id,
            ] : null,

            'invoices' => $admission->invoiceAdmission
                ->map(fn($line) => [
                    'invoice_admission_id' => $line->invoice_admission_id,
                    'price' => round((float) $line->price, 2),
                    'admission_period_id' => $line->admission_period_id,
                    'period_code' => AdmissionPeriod::codeFor($line->admission_period_id),
                    'parent_admission_period_id' => $line->admissionPeriod?->parent_admission_period_id,
                    'accommodation_status' => $line->status,
                    'accommodation_reason' => $line->admissionPeriod?->reason,
                    'period_start' => $line->admissionPeriod?->start_date,
                    'period_end' => $line->admissionPeriod?->end_date,
                    'moved_at' => $line->admissionPeriod?->created_at,
                    'contract' => self::contract($line->branchContract),
                ])
                ->values(),
        ]];
    }

    private function contract(mixed $contract): ?array
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

    private function scheduleContext(object $patient)
    {
        ['adl' => $adl, 'medical' => $medical] = self::pickSchedules($patient->schedules);

        return [
            'adl' => $adl
                ? self::schedulePayload($adl, $patient)
                : null,

            'medical' => $medical
                ? self::schedulePayload($medical, $patient)
                : null,
        ];
    }


    private function access(PatientAccess $access)
    {
        return [
            'relationship_type' => $access->relationship_type,
            'have_access' => $access->have_access,
            'granted_at' => $access->created_at?->format('Y-m-d'),
        ];
    }

    private function patient(object $patient)
    {
        return [
            'patient_id' => $patient->patient_id,
            'uuid' => $patient->uuid,
            'full_name' => trim(
                preg_replace('/\s+/', ' ', "{$patient->first_name} {$patient->middle_name} {$patient->last_name}")
            ),
            'avatar' => $patient->avatar,
            'gender' => $patient->gender,
            'date_of_birth' => $patient->date_of_birth?->format('Y-m-d'),
            'phone_number' => $patient->phone_number,
            'blood_type' => $patient->blood_type,
            'allergies' => $patient->allergies ?? [],
        ];
    }

    private function patientDetails(object $patient)
    {
        return [
            'full_address' => $patient->location?->full_address,
            'assessments' => self::assessments($patient),
            'diagnoses' => self::diagnoses($patient),
        ];
    }

    private function medication(object $patient)
    {
        return $patient->medications
            ->map(fn($medication) => MedicationPresenter::medication($medication))
            ->concat(
                $patient->vitals->map(fn($vital) => MedicationPresenter::vital($vital))
            )
            ->values();
    }

    private function assessments(object $patient)
    {
        return collect($patient->assessments ?? [])
            ->map(fn($assessment) => [
                'recorded_at' => $assessment->created_at?->toDateString(),
                'condition' => $assessment->condition,
                'mental_state' => $assessment->mental_state,
                'affect' => $assessment->affect,
                'behavior' => $assessment->behavior,
                'communication' => $assessment->communication,
                'speech' => $assessment->speech,
                'life_system_profile' => $assessment->life_system_profile,
            ])
            ->values();
    }

    private function diagnoses(object $patient)
    {
        return collect($patient->diagnoses ?? [])
            ->map(fn($diagnosis) => [
                'diagnosis' => $diagnosis->diagnosis,
                'diagnosis_date' => $diagnosis->diagnosis_date?->toDateString(),
                'diagnosis_notes' => $diagnosis->diagnosis_notes,
                'diagnosis_file' => $diagnosis->diagnosis_file,
            ])
            ->values();
    }

    private function invoicePayments(object $invoice)
    {
        $rows = [];

        foreach ($invoice->allocations as $allocation) {
            $payment = $allocation->payment;
            $amount = (float) $allocation->amount;

            $key = $allocation->payment_id . ($amount < 0 ? '-out' : '-in');

            if (!isset($rows[$key])) {
                $rows[$key] = [
                    'payment_id' => $allocation->payment_id,
                    'payment_code' => $payment?->payment_code,
                    'reference_id' => $payment?->reference_id,
                    'amount' => 0.0,
                    'description' => $allocation->description,
                    'payment_method' => $payment?->payment_method,
                    'masked_account_detail' => $payment?->masked_account_detail,
                    'created_at' => $payment?->created_at?->format('Y-m-d H:i:s'),
                    'refunds' => [],
                ];
            }

            $rows[$key]['amount'] = round($rows[$key]['amount'] + $amount, 2);
            foreach ($allocation->refundAllocations as $line) {
                $credit = $line->refund;
                $withdrawal = $credit?->transaction;
                $refundKey = $line->refund_id;

                if (!isset($rows[$key]['refunds'][$refundKey])) {
                    $rows[$key]['refunds'][$refundKey] = [
                        'refund_id' => $line->refund_id,
                        'amount' => 0.0,
                        'refund_total' => (float) ($credit?->amount ?? 0),
                        'reason' => $line->invoiceAdjustment?->reason,
                        'refund_method' => $withdrawal?->method,
                        'refund_code' => $withdrawal?->transaction_code,
                        'status' => $withdrawal?->status ?? 'credited',
                        'declined_reason' => $withdrawal?->declined_reason,
                        'masked_account_detail' => $withdrawal?->masked_account_number,
                        'account_name' => $withdrawal?->party_name,
                        'created_at' => $credit?->created_at?->format('Y-m-d H:i:s'),
                    ];
                }

                $rows[$key]['refunds'][$refundKey]['amount'] = round(
                    $rows[$key]['refunds'][$refundKey]['amount'] + (float) $line->amount,
                    2
                );
            }
        }

        return collect($rows)
            ->map(function ($row) {
                $row['refunds'] = array_values($row['refunds']);

                return $row;
            })
            ->values()
            ->toArray();
    }


    private function transactions(Collection $invoices)
    {
        $payments = [];
        $refunds = [];

        foreach ($invoices as $invoice) {
            $code = $invoice->invoice_code;

            foreach ($invoice->allocations as $allocation) {
                $payment = $allocation->payment;
                $amount = (float) $allocation->amount;
                $key = 'payment-' . $allocation->payment_id . ($amount < 0 ? '-out' : '-in');

                if (!isset($payments[$key])) {
                    $payments[$key] = [
                        'id' => $key,
                        'type' => 'payment',
                        'payment_id' => $allocation->payment_id,
                        'amount' => 0.0,
                        'payment_method' => $payment?->payment_method,
                        'payment_code' => $payment?->payment_code,
                        'reference_id' => $payment?->reference_id,
                        'masked_account_detail' => $payment?->masked_account_detail,
                        'status' => 'completed',
                        'created_at' => $payment?->created_at?->format('Y-m-d H:i:s'),
                        'invoice_codes' => [],
                    ];
                }

                $payments[$key]['amount'] = round($payments[$key]['amount'] + $amount, 2);
                $payments[$key]['invoice_codes'][] = $code;

                foreach ($allocation->refundAllocations as $line) {
                    $credit = $line->refund;
                    $withdrawal = $credit?->transaction;
                    $refundKey = 'refund-' . $line->refund_id;

                    if (!isset($refunds[$refundKey])) {
                        $refunds[$refundKey] = [
                            'id' => $refundKey,
                            'type' => 'refund',
                            'refund_id' => $line->refund_id,
                            'amount' => 0.0,
                            'reason' => $line->invoiceAdjustment?->reason,
                            'refund_method' => $withdrawal?->method,
                            'refund_code' => $withdrawal?->transaction_code,
                            'masked_account_detail' => $withdrawal?->masked_account_number,
                            'account_name' => $withdrawal?->party_name,
                            'declined_reason' => $withdrawal?->declined_reason,
                            'status' => $withdrawal?->status ?? 'credited',
                            'created_at' => $credit?->created_at?->format('Y-m-d H:i:s'),
                            'invoice_codes' => [],
                        ];
                    }

                    $refunds[$refundKey]['amount'] = round(
                        $refunds[$refundKey]['amount'] + (float) $line->amount,
                        2
                    );

                    $refunds[$refundKey]['invoice_codes'][] = $code;
                }
            }

        }

        return collect($payments)
            ->concat($refunds)
            ->map(function ($entry) {
                $entry['invoice_codes'] = array_values(array_unique($entry['invoice_codes']));

                return $entry;
            })
            ->sortByDesc('created_at')
            ->values()
            ->toArray();
    }

    private function balances(Collection $invoices, bool $full = false)
    {
        $billed = $invoices->filter(fn($invoice) => in_array($invoice->status, [
            Invoice::STATUS_PENDING,
            Invoice::STATUS_PARTIAL,
            Invoice::STATUS_PAID,
        ], true));

        $refunds = $invoices
            ->flatMap(fn($invoice) => $invoice->allocations)
            ->flatMap(fn($allocation) => $allocation->refundAllocations)
            ->pluck('refund')
            ->filter()
            ->unique('refund_id');

        $balances = [
            'patient_balance' => (float) $billed->sum('balance_due'),
            'patient_refundable' => round(
                (float) $refunds->filter(fn($refund) => $refund->is_available)->sum('amount'),
                2
            ),
        ];

        if ($full) {
            $balances += [
                'patient_pending_withdrawal' => round(
                    (float) $refunds
                        ->filter(fn($refund) => $refund->transaction?->type === Transaction::TYPE_WITHDRAW
                            && $refund->transaction?->status === Transaction::STATUS_REQUESTED)
                        ->sum('amount'),
                    2
                ),
                'patient_adjusted' => (float) $billed->sum('adjusted_total'),
            ];
        }

        return $balances;
    }

    private function organization(object $patient)
    {
        return [
            'branch_id' => $patient->branch_id,
            'uuid' => $patient->branch?->uuid,
            'name' => $patient->branch?->name,
            'full_address' => $patient->branch?->location?->full_address,
        ];
    }

    private function client(?object $client)
    {
        if (!$client) {
            return null;
        }

        return [
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'phone_number' => $client->phone_number,
            'email' => $client->user?->email,
        ];
    }

    private function locationContext(object $patient)
    {
        if ($patient->currentAdmission) {
            return self::admissionContext(
                $patient->currentAdmission,
                'facility',
                'admitted'
            );
        }

        $homecare = self::currentHomecareSchedules($patient);

        if ($homecare->isNotEmpty()) {
            ['adl' => $adl, 'medical' => $medical] = self::pickSchedules($homecare);

            if ($adl || $medical) {
                return [
                    'type' => 'homecare',
                    'status' => $adl?->status ?? $medical?->status,
                ];
            }
        }

        if ($patient->latestAdmission) {
            return self::admissionContext(
                $patient->latestAdmission,
                'admission_fallback',
                $patient->latestAdmission->status
            );
        }

        ['adl' => $adl, 'medical' => $medical] = self::pickSchedules($patient->schedules);

        if ($adl || $medical) {
            return [
                'type' => 'homecare',
                'status' => $adl?->status ?? $medical?->status,
            ];
        }

        return [
            'type' => 'none',
            'status' => 'no_active_record',
            'note' => 'Patient has no active or historical admission/homecare records',
        ];
    }

    private function admissionContext(object $admission,  string $type, string $status)
    {
        return [
            'type' => $type,
            'status' => $admission?->status,
            'admission' => [
                'patient_admission_id' => $admission->patient_admission_id,
                'status' => $admission->status,
                'admitted_at' => $admission->admitted_at?->format('Y-m-d H:i:s'),
                'end_date' => $admission->end_date?->format('Y-m-d H:i:s'),
            ],
            'bed' => [
                'bed_no' => $admission->bed?->bed_no,
                'status' => $admission->bed?->status,
            ],
            'room' => $admission->bed?->room ? [
                'room_no' => $admission->bed->room->room_no,
                'room_type' => $admission->bed->room->room_type,
                'floor' => $admission->bed->room->floor,
            ] : null,
        ];
    }

    public function schedulePayload(Schedule $schedule, object $patient)
    {
        $totalMinutes = $schedule->scheduleServices->sum(
            fn($scheduleService) => self::resolveDurationMinutes($scheduleService)
        );

        $totalHours = round($totalMinutes / 60, 2);

        $startTime = $schedule->scheduled_at;

        $endTime = $startTime && $totalMinutes
            ? $startTime->copy()->addMinutes($totalMinutes)
            : null;

        $isOnsite = $schedule->category === Schedule::CATEGORYFACILITY;
        $serviceLocation = $schedule->location ?? $patient->location;
        $serviceAddress = $serviceLocation?->full_address;

        return [
            'schedule_id' => $schedule->schedule_id,
            'schedule_code' => $schedule->schedule_code,
            'status' => $schedule->status,
            'category' => $schedule->category,

            'scheduled_at' => $schedule->scheduled_at?->toISOString(),
            'scheduled_date' => $schedule->scheduled_at?->format('Y-m-d'),

            'start_time' => $startTime?->format('g:i A'),
            'end_time' => $endTime?->format('g:i A'),
            'total_duration_minutes' => $totalMinutes,
            'total_hours' => (float) $totalHours,

            'type' => $schedule->scheduleServices->contains(
                fn($service) => $service->hours_booked !== null
            ) ? 'adl' : 'medical',

            'is_onsite' => $isOnsite,
            'address' => $isOnsite
                ? 'On-site'
                : ($serviceAddress ?? 'No address on file'),

            'patient' => [
                'patient_id' => $patient->patient_id,
                'patient_uuid' => $patient->uuid,
                'full_name' => trim(
                    "{$patient->first_name} {$patient->last_name}"
                ),
                'address' => $patient->location?->full_address,
            ],

            'services' => $schedule->scheduleServices
                ->map(fn($scheduleService) => self::scheduleServicePayload($scheduleService, $patient))
                ->values()
                ->toArray(),
        ];
    }

    private function scheduleServicePayload(object $scheduleService, object $patient)
    {
        return [
            'schedule_services_id' => $scheduleService->schedule_services_id,
            'service_id' => $scheduleService->service_id,
            'service_name' => $scheduleService->service?->service_name,

            'hours_booked' => $scheduleService->hours_booked !== null
                ? (float) $scheduleService->hours_booked
                : null,

            'price' => (float) ($scheduleService->invoiceServices->first()->price ?? 0),

            'duration_minutes' => self::resolveDurationMinutes($scheduleService),
            'type' => $scheduleService->type,

            'assignees' => $scheduleService->assigned
                ->map(fn($assignment) => self::assigneePayload($assignment, $patient))
                ->values()
                ->toArray(),
        ];
    }

    private function assigneePayload(object $assignment, object $patient)
    {
        $employee = $assignment->employee?->employees;

        return [
            'employee_id' => $assignment->employee_id,
            'is_active' => $assignment->is_active,
            'full_name' => $employee?->full_name ?? '',

            'employee_role' => $employee?->employeeBranch
                ?->firstWhere('branch_id', $patient->branch_id)
                ?->role_name,

            'avatar' => $employee?->avatar,
            'note' => $assignment->note,

            'online' => $assignment->onlineSchedules
                ->filter(fn($online) => $online->in_timestamp !== null)
                ->map(fn($online) => [
                    'online_schedule_id' => $online->online_schedule_id,
                    'type_in' => $online->type_in,
                    'type_out' => $online->type_out,
                    'in_timestamp' => $online->in_timestamp?->toISOString(),
                    'out_timestamp' => $online->out_timestamp?->toISOString(),
                    'notes' => $online->notes,
                ])
                ->values()
                ->toArray(),
        ];
    }

    private function resolveDurationMinutes(object $scheduleService)
    {
        if ($scheduleService->hours_booked !== null) {
            return (int) round(
                ((float) $scheduleService->hours_booked) * 60
            );
        }

        $maxDuration = $scheduleService->service?->maximum_duration;

        if (!$maxDuration) {
            return 0;
        }

        if ($maxDuration instanceof \Carbon\CarbonInterface) {
            return ($maxDuration->hour * 60)
                + $maxDuration->minute;
        }

        [$hours, $minutes] = array_pad(
            explode(':', (string) $maxDuration),
            2,
            0
        );

        return ((int) $hours * 60)
            + (int) $minutes;
    }



    private function invoices(Collection $invoices, bool $voided = false)
    {
        return $invoices
            ->filter(
                fn($invoice) => $voided
                    ? $invoice->status === Invoice::STATUS_VOID
                    : $invoice->status !== Invoice::STATUS_VOID
            )
            ->values()
            ->map(fn($invoice) => $this->invoiceRow($invoice))
            ->values()
            ->toArray();
    }

    public function invoiceRow(Invoice $invoice): array
    {
        return [
            'invoice_id' => $invoice->invoice_id,
            'invoice_code' => $invoice->invoice_code,
            'description' => $invoice->paymentDescription(),
            'status' => $invoice->status,
            'total' => (float) $invoice->total_amount,
            'adjusted_total' => (float) $invoice->adjusted_total,
            'amount_paid' => (float) $invoice->amount_paid,
            'net_paid' => (float) $invoice->net_paid_amount,
            'balance_due' => (float) $invoice->balance_due,
            'refund_status' => $invoice->refund_status,
            'void_reason' => $invoice->void_reason,
            'voided_at' => $invoice->voided_at?->format('Y-m-d H:i:s'),
            'created_at' => $invoice->created_at?->format('Y-m-d H:i:s'),
            'payments' => self::invoicePayments($invoice),

            'adjustments' => $invoice->invoiceAdjustments
                ->map(fn($adjustment) => [
                    'invoice_adjustment_id' => $adjustment->invoice_adjustment_id,
                    'type' => $adjustment->type,
                    'amount' => (float) $adjustment->amount,
                    'reason' => $adjustment->reason,
                    'created_at' => $adjustment->created_at?->format('Y-m-d H:i:s'),
                ])
                ->values(),
        ];
    }

    public function invoiceDetail(Invoice $invoice): array
    {
        $lines = collect();

        foreach ($invoice->invoiceAdmissionLines as $line) {
            $period = $line->admissionPeriod;

            $lines->push([
                'category' => 'Accommodation',
                'description' => $line->description,
                'detail' => $period
                    ? collect([
                        $period->start_date ? Carbon::parse($period->start_date)->format('M j, Y') : null,
                        $period->end_date ? Carbon::parse($period->end_date)->format('M j, Y') : null,
                    ])->filter()->implode(' – ')
                    : null,
                'amount' => (float) $line->price,
            ]);
        }

        foreach ($invoice->invoiceServices as $line) {
            $scheduleService = $line->scheduleService;
            $isAdl = $scheduleService?->service_id === null;
            $hours = (float) ($scheduleService?->hours_booked ?? 0);

            $lines->push([
                'category' => $isAdl ? 'Daily Living (ADL)' : 'Service',
                'description' => $line->description,
                'detail' => $isAdl && $hours > 0
                    ? rtrim(rtrim(number_format($hours, 2), '0'), '.') . ' hrs × ₱' . number_format((float) $line->price, 2)
                    : null,
                'amount' => round((float) $line->price * ($isAdl ? max(1, $hours) : 1), 2),
            ]);
        }

        foreach ($invoice->additionalCharges as $charge) {
            $lines->push([
                'category' => $charge->type_label,
                'description' => $charge->description,
                'detail' => null,
                'amount' => (float) $charge->amount,
            ]);
        }

        return $this->invoiceRow($invoice) + [
            'lines' => $lines->values()->all(),
        ];
    }
}
