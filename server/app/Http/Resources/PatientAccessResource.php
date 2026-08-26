<?php

namespace App\Http\Resources;

use App\Utils\MedicationPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientAccessResource extends JsonResource
{
    protected bool $extended = false;

    public function __construct($resource, bool $extended = false)
    {
        parent::__construct($resource);
        $this->extended = $extended;
    }

    public function toArray(Request $request): array
    {
        return [
            'access' => $this->formatAccess(),
            'patient' => $this->formatPatient(),
            'patient_balance' => $this->getPatientBalance(),
            'patient_refundable' => $this->getPatientRefundable(),
            'patient_adjusted' => $this->getPatientAdjusted(),
            'organization' => $this->formatOrganization(),
            'location_context' => $this->formatLocationContext(),
            'latest_invoice' => $this->formatLatestInvoice(),
            'invoices' => $this->formatInvoices(),
            'client' => $this->extended ? $this->formatClient() : null,
            'schedule' => $this->formatScheduleContext(),
        ];
    }

    private function formatAccess(): array
    {
        return [
            'relationship_type' => $this->resource->patientAccess?->relationship_type,
            'have_access' => $this->resource->patientAccess?->have_access ?? true,
        ];
    }

    private function formatPatient(): array
    {
        $data = [
            'patient_id' => $this->patient_id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'phone_number' => $this->phone_number,
            'blood_type' => $this->blood_type,
        ];

        if ($this->extended) {
            $data += [
                'full_name' => trim("{$this->first_name} {$this->middle_name} {$this->last_name}"),
                'full_address' => $this->location?->full_address,
                'medication' => $this->resource->medications
                    ->map(fn($medication) => MedicationPresenter::medication($medication))
                    ->concat(
                        $this->resource->vitals->map(fn($vital) => MedicationPresenter::vital($vital))
                    )
                    ->values(),
            ];
        }

        return $data;
    }

    private function getPatientBalance(): float
    {
        $billing = $this->resource->billing_summary ?? [];

        return (float) ($billing['balance_due'] ?? 0);
    }

    private function getPatientRefundable(): float
    {
        $billing = $this->resource->billing_summary ?? [];

        return (float) ($billing['refundable'] ?? 0);
    }

    private function getPatientAdjusted(): float
    {
        $billing = $this->resource->billing_summary ?? [];

        return (float) ($billing['adjusted'] ?? 0);
    }

    private function formatOrganization(): array
    {
        return [
            'branch_id' => $this->branch_id,
            'name' => $this->branch?->name,
            'location_id' => $this->location_id,
            'full_address' => $this->branch?->location?->full_address,
        ];
    }

    private function formatLocationContext(): array
    {
        if ($this->resource->currentAdmission) {
            return $this->formatAdmissionContext(
                $this->resource->currentAdmission,
                'facility',
                'admitted'
            );
        }

        if ($this->resource->latestAdmission) {
            return $this->formatAdmissionContext(
                $this->resource->latestAdmission,
                'admission_fallback',
                $this->resource->latestAdmission->status
            );
        }

        if ($this->resource->relationLoaded('schedules') && $this->resource->schedules->isNotEmpty()) {
            return $this->formatHomecareContext();
        }

        return [
            'type' => 'none',
            'status' => 'no_active_record',
            'note' => 'Patient has no active or historical admission/homecare records',
        ];
    }

    private function formatAdmissionContext(object $admission, string $type, string $status): array
    {
        return [
            'type' => $type,
            'status' => $status,
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

    private function formatHomecareContext(): array
    {
        $adlSchedule = $this->getScheduleByType('adl');
        $medicalSchedule = $this->getScheduleByType('medical');

        return [
            'type' => 'homecare',
            'status' => $adlSchedule?->status ?? $medicalSchedule?->status,
            'adl' => $adlSchedule ? $this->formatSchedulePayload($adlSchedule) : null,
            'medical' => $medicalSchedule ? $this->formatSchedulePayload($medicalSchedule) : null,
        ];
    }

    private function getScheduleByType(string $type): mixed
    {
        if (!$this->resource->relationLoaded('schedules')) {
            return null;
        }

        return $this->resource->schedules->first(function ($schedule) use ($type) {
            if ($type === 'adl') {
                return $schedule->scheduleServices->contains(
                    fn($service) => $service->type === 'ADL'
                );
            }

            return $schedule->scheduleServices->contains(
                fn($service) => $service->type !== 'ADL' && $service->service_id !== null
            );
        });
    }

    private function formatLatestInvoice(): ?array
    {
        $invoices = $this->formatInvoices();

        return $invoices[0] ?? null;
    }

    private function formatInvoices(): array
    {
        if (!$this->resource->relationLoaded('patient_invoices')) {
            return [];
        }

        return $this->resource->patient_invoices
            ->map(fn($invoice) => $this->formatInvoicePayload($invoice))
            ->values()
            ->toArray();
    }

    private function formatInvoicePayload(object $invoice): array
    {
        return [
            'invoice_id' => $invoice->invoice_id,
            'invoice_code' => $invoice->invoice_code,
            'status' => $invoice->status,
            'total' => (float) $invoice->total,
            'adjusted_total' => (float) $invoice->adjusted_total,
            'amount_paid' => (float) $invoice->amount_paid,
            'balance_due' => (float) $invoice->balance_due,
            'refund_status' => $invoice->refund_status,
            'created_at' => $invoice->created_at?->format('Y-m-d H:i:s'),

            'source' => $this->formatInvoiceSource($invoice),

            'payments' => $this->formatPayments($invoice),

            'adjustments' => $this->formatAdjustments($invoice),
        ];
    }

    private function formatInvoiceSource(object $invoice): ?array
    {
        if ($invoice->invoiceFacility && $invoice->invoiceFacility->isNotEmpty()) {
            $facility = $invoice->invoiceFacility->first();

            return [
                'type' => 'Facility Admission',
                'patient_admission_id' => $facility->patient_admission_id,
                'start_date' => $facility->start_date?->format('Y-m-d'),
                'end_date' => $facility->end_date?->format('Y-m-d'),
                'admission_status' => $facility->patientAdmission?->status,
                'admitted_at' => $facility->patientAdmission?->admitted_at?->format('Y-m-d H:i:s'),

                'contract' => $facility->branchContract ? [
                    'branch_contract_id' => $facility->branchContract->branch_contract_id,
                    'category' => $facility->branchContract->category,
                    'accommodation_type' => $facility->branchContract->accommodation_type,
                    'billing_cycle' => $facility->branchContract->billing_cycle,
                    'price' => (float) $facility->branchContract->price,
                ] : null,
            ];
        }

        if ($invoice->invoiceServices && $invoice->invoiceServices->isNotEmpty()) {
            return [
                'services' => $invoice->invoiceServices
                    ->map(fn($invoiceService) => [
                        'type' => $invoiceService->scheduleService?->schedule?->category === 'Facility'
                            ? 'Medical Service'
                            : $invoiceService->scheduleService?->type,

                        'schedule_services_id' => $invoiceService->schedule_services_id,

                        'price' => (float) $invoiceService->price,

                        'hours_booked' => $invoiceService->scheduleService?->hours_booked !== null
                            ? (float) $invoiceService->scheduleService->hours_booked
                            : null,

                        'service' => $invoiceService->scheduleService?->service ? [
                            'service_id' => $invoiceService->scheduleService->service->service_id,
                            'service_name' => $invoiceService->scheduleService->service->service_name,
                            'type' => $invoiceService->scheduleService->service->type,
                        ] : null,

                        'schedule' => $invoiceService->scheduleService?->schedule ? [
                            'schedule_id' => $invoiceService->scheduleService->schedule->schedule_id,
                            'schedule_code' => $invoiceService->scheduleService->schedule->schedule_code,
                            'scheduled_at' => $invoiceService->scheduleService->schedule->scheduled_at?->format('Y-m-d H:i:s'),
                        ] : null,
                    ])
                    ->values(),
            ];
        }

        return null;
    }

    private function formatPayments(object $invoice): array
    {
        if (!$invoice->relationLoaded('payments')) {
            return [];
        }

        return $invoice->payments
            ->map(fn($payment) => [
                'payment_id' => $payment->payment_id,
                'reference_id' => $payment->reference_id,
                'amount' => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
                'created_at' => $payment->created_at?->format('Y-m-d H:i:s'),

                'refunds' => $this->formatRefunds($payment),
            ])
            ->values()
            ->toArray();
    }

    private function formatRefunds(object $payment): array
    {
        if (!$payment->relationLoaded('refunds')) {
            return [];
        }

        return $payment->refunds
            ->map(fn($refund) => [
                'refund_id' => $refund->refund_id,
                'amount' => (float) $refund->amount,
                'refund_method' => $refund->refund_method,
                'reference_id' => $refund->reference_id,
                'status' => $refund->status,
                'reason' => $refund->reason,
                'created_at' => $refund->created_at?->format('Y-m-d H:i:s'),
            ])
            ->values()
            ->toArray();
    }

    private function formatAdjustments(object $invoice): array
    {
        if (!$invoice->relationLoaded('invoiceAdjustments')) {
            return [];
        }

        return $invoice->invoiceAdjustments
            ->map(fn($adjustment) => [
                'invoice_adjustment_id' => $adjustment->invoice_adjustment_id,
                'type' => $adjustment->type,
                'amount' => (float) $adjustment->amount,
                'reason' => $adjustment->reason,
                'created_at' => $adjustment->created_at?->format('Y-m-d H:i:s'),
            ])
            ->values()
            ->toArray();
    }

    private function formatClient(): array
    {
        $client = $this->resource->patientAccess?->client;

        return [
            'client_id' => $client?->client_id,
            'user_id' => $client?->user_id,
            'location_id' => $client?->location_id,
            'first_name' => $client?->first_name,
            'last_name' => $client?->last_name,
            'phone_number' => $client?->phone_number,
            'avatar' => $client?->avatar,
            'is_verified' => $client?->is_verified ?? false,
            'created_at' => $client?->created_at?->format('Y-m-d\TH:i:s.u\Z'),
            'updated_at' => $client?->updated_at?->format('Y-m-d\TH:i:s.u\Z'),
        ];
    }

    private function formatScheduleContext(): array
    {
        if (!$this->resource->relationLoaded('schedules') || $this->resource->schedules->isEmpty()) {
            return [
                'adl' => null,
                'medical' => null,
            ];
        }

        $adlSchedule = $this->getScheduleByType('adl');
        $medicalSchedule = $this->getScheduleByType('medical');

        return [
            'adl' => $adlSchedule ? $this->formatSchedulePayload($adlSchedule) : null,
            'medical' => $medicalSchedule ? $this->formatSchedulePayload($medicalSchedule) : null,
        ];
    }

    private function formatSchedulePayload(object $schedule): array
    {
        return [
            'schedule_id' => $schedule->schedule_id,
            'schedule_code' => $schedule->schedule_code,
            'scheduled_at' => $schedule->scheduled_at?->format('Y-m-d H:i:s'),
            'category' => $schedule->category,
            'status' => $schedule->status,

            'services' => $schedule->relationLoaded('scheduleServices')
                ? $schedule->scheduleServices
                ->map(fn($service) => [
                    'schedule_services_id' => $service->schedule_services_id,
                    'type' => $service->type,
                    'hours_booked' => $service->hours_booked !== null
                        ? (float) $service->hours_booked
                        : null,

                    'service' => $service->relationLoaded('service') && $service->service ? [
                        'service_id' => $service->service->service_id,
                        'service_name' => $service->service->service_name,
                        'type' => $service->service->type,
                    ] : null,

                    'assigned' => $this->formatAssignedPayload($service),
                ])
                ->values()
                ->toArray()
                : [],
        ];
    }

    private function formatAssignedPayload(object $scheduleService): ?array
    {
        if (!$scheduleService->relationLoaded('assigned') || $scheduleService->assigned->isEmpty()) {
            return null;
        }

        $assigned = $scheduleService->assigned->first();

        $onlineSchedule = $assigned->relationLoaded('onlineSchedules')
            ? $assigned->onlineSchedules->first()
            : null;

        return [
            'schedule_assigned_id' => $assigned->schedule_assigned_id,
            'employee_id' => $assigned->employee_id,
            'note' => $assigned->note,
            'is_active' => $assigned->is_active,

            'employee' => $assigned->relationLoaded('employee') && $assigned->employee ? [
                'employee_id' => $assigned->employee->employee_id,
                'first_name' => $assigned->employee->first_name,
                'last_name' => $assigned->employee->last_name,
            ] : null,

            'online_schedule' => $onlineSchedule ? [
                'online_schedule_id' => $onlineSchedule->online_schedule_id,
                'qr_in_token' => $onlineSchedule->qr_in_token,
                'qr_out_token' => $onlineSchedule->qr_out_token,
                'in_timestamp' => $onlineSchedule->in_timestamp?->format('Y-m-d H:i:s'),
                'out_timestamp' => $onlineSchedule->out_timestamp?->format('Y-m-d H:i:s'),
                'notes' => $onlineSchedule->notes,
            ] : null,
        ];
    }
}
