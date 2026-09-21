<?php

namespace App\Http\Resources;

use App\Models\Invoice;
use App\Utils\InvoiceMoney;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'invoice_id'   => $this->invoice_id,
            'invoice_code' => $this->invoice_code,
            'description'  => $this->when(
                $this->resource->relationLoaded('invoiceAdmissionLines')
                    && $this->resource->relationLoaded('invoiceServices'),
                fn() => $this->paymentDescription()
            ),
            'total'        => (float) $this->total_amount,
            'adjusted_total' => $this->adjusted_total,
            'written_off_amount' => $this->status === Invoice::STATUS_WRITTEN_OFF
                ? round(max(0, $this->adjusted_total - $this->net_paid_amount), 2)
                : 0.0,
            'write_off_reason' => $this->write_off_reason,
            'amount_paid'  => $this->amount_paid,
            'refunded_amount'          => $this->refunded_amount,
            'refund_requested_amount' => InvoiceMoney::pendingWithdrawal($this->resource),
            'refund_status'            => $this->refund_status,
            'balance_due'  => $this->balance_due,
            'status'       => $this->resolveStatus(),
            'created_at'   => $this->created_at?->toIso8601String(),

            'patient' => $this->resolvePatient(),

            'branch' => $this->whenLoaded('branch', fn() => [
                'branch_id' => $this->branch->branch_id,
                'name'      => $this->branch->name ?? null,
            ]),

            'services' => $this->whenLoaded(
                'invoiceServices',
                fn() =>
                $this->invoiceServices->map(function ($service) {
                    $scheduleService = $service->scheduleService;
                    $isAdl = $scheduleService?->service_id === null;

                    $quantity = $isAdl
                        ? (float) ($scheduleService?->hours_booked ?? 0)
                        : 1;

                    return [
                        'schedule_services_id' => $service->schedule_services_id,
                        'price'                => (float) $service->price,
                        'amount'               => (float) $service->price * $quantity,
                        'note'                 => $service->note,
                        'description'          => $service->description,
                        'service_name'         => $isAdl
                            ? 'Activity of Daily Living (ADL)'
                            : ($scheduleService?->service?->service_name ?? null),
                        'type'                 => $isAdl ? 'ADL' : 'Medical',
                        'quantity'             => $quantity,
                    ];
                })
            ),

            'facilities' => $this->whenLoaded(
                'invoiceAdmissionLines',
                fn() =>
                $this->invoiceAdmissionLines->map(fn($facility) => [
                    'invoice_admission_id' => $facility->invoice_admission_id,
                    'admission_period_id'  => $facility->admission_period_id,
                    'branch_contract_id'   => $facility->branchContract?->branch_contract_id,
                    'price'                => (float) $facility->price,
                    'description'          => $facility->description,
                    'patient_admission_id' =>$facility->patientAdmission?->patient_admission_id,

                    'patient_name' => trim(
                        ($facility->patientAdmission->patient->first_name ?? '') . ' ' .
                        ($facility->patientAdmission->patient->last_name ?? '')
                    ),
                ])
            ),

            // Through the allocations: a payment split across invoices only
            // contributes its own share, and its refunds here are the ones
            // raised against this invoice.
            'payments' => $this->whenLoaded(
                'allocations',
                fn() =>
                $this->allocations->map(fn($allocation) => [
                    'payment_id'     => $allocation->payment_id,
                    'allocation_id'  => $allocation->allocation_id,
                    'payment_code'   => $allocation->payment?->payment_code,
                    'reference_id'   => $allocation->payment?->reference_id,
                    'amount'         => (float) $allocation->amount,
                    'description'    => $allocation->description,
                    'payment_method' => $allocation->payment?->payment_method,
                    'created_at'     => $allocation->payment?->created_at?->toIso8601String(),

                    'refunds' => $allocation->refundAllocations->map(fn($line) => [
                        'refund_id'           => $line->refund_id,
                        'refund_code'         => $line->refund?->transaction?->transaction_code,
                        'amount'              => (float) $line->amount,
                        'refund_total'        => (float) ($line->refund?->amount ?? 0),
                        'reason'              => $line->invoiceAdjustment?->reason,
                        'status'              => $line->refund?->transaction?->status,
                        'refund_method'       => $line->refund?->transaction?->method,
                        'declined_reason'     => $line->refund?->transaction?->declined_reason,
                        'masked_account_detail' => $line->refund?->transaction?->masked_account_number,
                        'created_at'          => $line->refund?->created_at?->toIso8601String(),
                    ])->values(),
                ])->values()
            ),

            'adjustments' => $this->whenLoaded(
                'invoiceAdjustments',
                fn() =>
                $this->invoiceAdjustments->map(fn($adjustment) => [
                    'invoice_adjustment_id' => $adjustment->invoice_adjustment_id,
                    'type'                  => $adjustment->type,
                    'amount'                => (float) $adjustment->amount,
                    'reason'                => $adjustment->reason,
                    'created_at'            => $adjustment->created_at?->toIso8601String(),
                ])
            ),

            /*
            |--------------------------------------------------------------------------
            | DISCHARGE CALCULATION
            |--------------------------------------------------------------------------
            */

            'discharge_calculation' => $this->when(
                $this->resource->relationLoaded('invoiceAdmissionLines'),
                fn() => $this->resolveDischargeCalculation()
            ),
        ];
    }

    /**
     * Calculate the discharge amount for the facility invoice.
     *
     * Provides:
     * - Normal refund calculation
     * - Discharge-today calculation
     */
    protected function resolveDischargeCalculation(): ?array
    {
        $facility = $this->invoiceAdmissionLines->first();

        if (!$facility) {
            return null;
        }

        $admission = $facility->patientAdmission;

        if (!$admission) {
            return null;
        }

        $contract = $facility->branchContract;

        if (!$contract) {
            return null;
        }

        $admissionDate = Carbon::parse(
            $admission->admission_date ?? $admission->created_at
        )->startOfDay();

        $today = now()->startOfDay();

        $billingCycle = strtoupper(
            trim($contract->billing_cycle ?? '')
        );

        $contractPrice = (float) $facility->price;

        $paidAmount = max(
            0,
            (float) $this->net_paid_amount
        );

        /*
        |--------------------------------------------------------------------------
        | DAYS
        |--------------------------------------------------------------------------
        */

        $daysSinceAdmission = max(
            0,
            $admissionDate->diffInDays($today)
        );

        /*
        |--------------------------------------------------------------------------
        | NORMAL REFUND
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | DISCHARGE TODAY
        |--------------------------------------------------------------------------
        |
        | Calculate only the portion actually consumed.
        |
        */

        $dischargeTodayBill = $this->calculateDischargeTodayBill(
            $contractPrice,
            $billingCycle,
            $admissionDate,
            $today
        );

        $dischargeTodayBalance = max(
            0,
            $dischargeTodayBill - $paidAmount
        );

        $dischargeTodayRefund = max(
            0,
            $paidAmount - $dischargeTodayBill
        );

        return [
            'admission_date' => $admissionDate->toDateString(),

            'calculation_date' => $today->toDateString(),

            'days_since_admission' => $daysSinceAdmission,

            'billing_cycle' => $billingCycle,

            'contract_price' => $contractPrice,

            'paid_amount' => $paidAmount,

            /*
            |--------------------------------------------------------------------------
            | DISCHARGE TODAY
            |--------------------------------------------------------------------------
            */

            'discharge_today' => [
                'bill_amount' => $dischargeTodayBill,

                'paid_amount' => $paidAmount,

                'balance' => $dischargeTodayBalance,

                'refund_amount' => $dischargeTodayRefund,

                'has_balance' =>
                    $dischargeTodayBalance > 0,

                'has_refund' =>
                    $dischargeTodayRefund > 0,
            ],
        ];
    }

    /**
     * Calculate the amount that should be billed
     * when the patient is discharged today.
     */
    protected function calculateDischargeTodayBill(
        float $contractPrice,
        string $billingCycle,
        Carbon $admissionDate,
        Carbon $today
    ): float {
        if ($today->lt($admissionDate)) {
            return 0;
        }

        $daysUsed =
            $admissionDate->diffInDays($today) + 1;

        /*
        |--------------------------------------------------------------------------
        | MONTHLY
        |--------------------------------------------------------------------------
        */

        if ($billingCycle === 'MONTHLY') {
            $daysInMonth = $admissionDate->daysInMonth;

            return round(
                ($contractPrice / $daysInMonth) * $daysUsed,
                2
            );
        }

        /*
        |--------------------------------------------------------------------------
        | YEARLY
        |--------------------------------------------------------------------------
        */

        if ($billingCycle === 'YEARLY') {
            $daysInYear = $admissionDate->isLeapYear()
                ? 366
                : 365;

            return round(
                ($contractPrice / $daysInYear) * $daysUsed,
                2
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 6 MONTHS
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $billingCycle,
                [
                    '6_MONTHS',
                    'SIX_MONTHS',
                    'SEMI_ANNUAL',
                ],
                true
            )
        ) {
            $periodEnd = $admissionDate
                ->copy()
                ->addMonths(6)
                ->subDay();

            $periodDays =
                $admissionDate->diffInDays($periodEnd) + 1;

            return round(   
                ($contractPrice / $periodDays) * $daysUsed,
                2
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FALLBACK
        |--------------------------------------------------------------------------
        */

        return $contractPrice;
    }

    /**
     * Resolve patient data.
     */
    protected function resolvePatient(): ?array
    {
        $patient = null;

        if ($this->resource->relationLoaded('invoiceAdmissionLines')) {
            $patient = $this->invoiceAdmissionLines
                ->first()?->patientAdmission?->patient;
        }

        if (
            !$patient &&
            $this->resource->relationLoaded('invoiceServices')
        ) {
            $patient = $this->invoiceServices
                ->first()?->scheduleService?->schedule?->patient;
        }

        if (!$patient) {
            return null;
        }

        return [
            'patient_id'    => $patient->patient_id,
            'full_name'     => trim(
                ($patient->first_name ?? '') . ' ' .
                ($patient->last_name ?? '')
            ) ?: null,
            'first_name'    => $patient->first_name,
            'middle_name'   => $patient->middle_name,
            'last_name'     => $patient->last_name,
            'gender'        => $patient->gender,
            'date_of_birth' => $patient->date_of_birth?->toDateString(),
            'age'           => $patient->date_of_birth?->age,
            'blood_type'    => $patient->blood_type,
            'phone_number'  => $patient->phone_number,
            'citizenship'   => $patient->citizenship,
        ];
    }

    /**
     * Derive invoice status.
     */
    protected function resolveStatus(): string
    {
        if (in_array($this->status, Invoice::CLOSED_STATUSES, true)) {
            return $this->status;
        }

        if ($this->amount_paid <= 0) {
            return Invoice::STATUS_PENDING;
        }

        if ($this->balance_due > 0) {
            return Invoice::STATUS_PARTIAL;
        }

        return Invoice::STATUS_PAID;
    }
}