<?php

namespace App\Http\Resources;

use App\Models\Schedule;
use App\Service\RefundService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'patient_id' => $this->patient_id,
            'uuid' => $this->uuid,
            'full_name' => trim("{$this->first_name} {$this->middle_name} {$this->last_name}"),
            'first_name' => $this->first_name,
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

            // Ongoing first, then pending, then everything else newest-first —
            // the overview shows the most actionable one at the top.
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
                    'address' => $schedule->location?->full_address,
                ])
                ->values()),

            'assessment' => $this->assessments
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
                ->values(),

            'diagnoses' => $this->diagnoses
                ->map(fn($diagnosis) => [
                    'uuid' => $diagnosis->uuid,
                    'diagnosis' => $diagnosis->diagnosis,
                    'diagnosis_date' => $diagnosis->diagnosis_date?->toDateString(),
                    'diagnosis_notes' => $diagnosis->diagnosis_notes,
                    'diagnosis_file' => $diagnosis->diagnosis_file,
                ])
                ->values(),

            'medications_count' => $this->medications_count ?? 0,
            'vitals_count' => $this->vitals_count ?? 0,

            'admissions' => $this->whenLoaded('admissions', function () {
                return $this->admissions
                    ->sortByDesc('created_at')
                    ->map(fn($admission) => $this->formatAdmission($admission))
                    ->values();
            }),

            'current_admission' => $this->whenLoaded('currentAdmission', function () {
                return $this->currentAdmission
                    ? $this->formatAdmission($this->currentAdmission, true)
                    : null;
            }),

            'latest_admission' => $this->whenLoaded('latestAdmission', function () {
                return $this->latestAdmission
                    ? $this->formatAdmission($this->latestAdmission)
                    : null;
            }),
        ];
    }

    private function formatAdmission(mixed $admission, bool $includeDischargeCalculation = false): array
    {
        $currentInvoice = $admission->currentInvoiceAccommodation;

        $invoice = $currentInvoice
            ?? $admission->invoiceAdmission
            ->sortByDesc('created_at')
            ->first();

        $contract = $invoice?->branchContract;

        return [
            'patient_admission_id' => $admission->patient_admission_id,
            'status' => $admission->status,
            'admitted_at' => $admission->admitted_at,
            'end_date' => $admission->end_date,
            'note' => $admission->note,

            'bed' => [
                'bed_id' => $admission->bed?->bed_id,
                'bed_no' => $admission->bed?->bed_no ?? 'N/A',
                'status' => $admission->bed?->status,
            ],

            'room' => [
                'room_id' => $admission->bed?->room?->room_id,
                'room_no' => $admission->bed?->room?->room_no ?? 'N/A',
                'room_type' => $admission->bed?->room?->room_type,
                'floor' => $admission->bed?->room?->floor,
            ],

            'current_contract' => $this->formatContract($contract),

            'current_invoice' => $this->formatInvoiceAccommodation($currentInvoice),

            'discharge_calculation' => ($includeDischargeCalculation && $invoice && $contract)
                ? app(RefundService::class)->getDischargeCalculation(
                    $invoice->invoice,
                    $admission,
                    $invoice
                )
                : null,

            'invoices' => $admission->relationLoaded('invoiceAdmission')
                ? $admission->invoiceAdmission
                ->map(fn($invoice) => $this->formatInvoiceAccommodation($invoice))
                ->values()
                : [],
        ];
    }

    private function formatInvoiceAccommodation(mixed $invoiceAccommodation): ?array
    {
        if (!$invoiceAccommodation) {
            return null;
        }

        $invoice = $invoiceAccommodation->invoice;

        return [
            'invoice_accommodation_id' => $invoiceAccommodation->invoice_accommodation_id,
            'invoice_id' => $invoiceAccommodation->invoice_id,
            'invoice_code' => $invoice?->invoice_code,
            'status' => $invoice?->status,
            'price' => $invoice?->total,

            'paid_amount' => $invoice?->amount_paid ?? 0,
            'refunded_amount' => $invoice?->refunded_amount ?? 0,
            'net_paid_amount' => $invoice?->net_paid_amount ?? 0,
            'refund_status' => $invoice?->refund_status ?? 'none',

            'start_date' => $invoiceAccommodation->start_date,
            'end_date' => $invoiceAccommodation->end_date,

            'contract' => $this->formatContract($invoiceAccommodation->branchContract),
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
