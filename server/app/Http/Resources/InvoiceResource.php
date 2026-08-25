<?php

namespace App\Http\Resources;

use App\Models\Invoice;
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
            'total'        => (float) $this->total,
            'amount_paid'  => $this->amount_paid,
            'refunded_amount'          => $this->refunded_amount,
            'refund_processing_amount' => $this->refunded_processing_amount,
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
                $this->invoiceServices->map(fn($service) => [
                    'schedule_services_id' => $service->schedule_services_id,
                    'price'                => (float) $service->price,
                    'note'                 => $service->note,
                    'service_name'         => $service->scheduleService?->service_id === null
                        ? 'Activity of Daily Living (ADL)'
                        : ($service->scheduleService->service->service_name ?? null),
                ])
            ),

            'facilities' => $this->whenLoaded(
                'invoiceFacility',
                fn() =>
                $this->invoiceFacility->map(fn($facility) => [
                    'invoice_facility_id'  => $facility->invoice_facility_id,
                    'branch_contract_id'   => $facility->branch_contract_id,
                    'price'                => (float) $facility->price,
                    'patient_admission_id' => $facility->patient_admission_id,

                    'patient_name' => trim(
                        ($facility->patientAdmission->patient->first_name ?? '') . ' ' .
                        ($facility->patientAdmission->patient->last_name ?? '')
                    ),
                ])
            ),

            'payments' => $this->whenLoaded(
                'payments',
                fn() =>
                $this->payments->map(fn($payment) => [
                    'payment_id'     => $payment->payment_id,
                    'reference_id'   => $payment->reference_id,
                    'amount'         => (float) $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'created_at'     => $payment->created_at?->toIso8601String(),

                    'refunds' => $payment->relationLoaded('refunds')
                        ? $payment->refunds->map(fn($refund) => [
                            'refund_id'           => $refund->refund_id,
                            'reference_id'        => $refund->reference_id,
                            'amount'              => (float) $refund->amount,
                            'status'              => $refund->status,
                            'refund_method'       => $refund->refund_method,
                            'reason'              => $refund->reason,
                            'masked_card_number'  => $refund->masked_card_number,
                            'created_at'          => $refund->created_at?->toIso8601String(),
                        ])
                        : [],
                ])
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
                $this->resource->relationLoaded('invoiceFacility'),
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
        $facility = $this->invoiceFacility->first();

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

        $terminationFeePercent = 20;

        $terminationFee = round(
            $contractPrice * ($terminationFeePercent / 100),
            2
        );

        $normalRefund = 0;

        if ($daysSinceAdmission <= 7) {
            $normalRefund = max(
                0,
                $paidAmount - $terminationFee
            );
        }

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
            | NORMAL
            |--------------------------------------------------------------------------
            */

            'normal' => [
                'within_termination_window' =>
                    $daysSinceAdmission <= 7,

                'termination_fee_percent' =>
                    $daysSinceAdmission <= 7
                        ? $terminationFeePercent
                        : 0,

                'termination_fee' =>
                    $daysSinceAdmission <= 7
                        ? $terminationFee
                        : 0,

                'refund_amount' =>
                    $normalRefund,
            ],

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

        if ($this->resource->relationLoaded('invoiceFacility')) {
            $patient = $this->invoiceFacility
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
        if ($this->amount_paid <= 0) {
            return Invoice::STATUS_PENDING;
        }

        if ($this->balance_due > 0) {
            return Invoice::STATUS_PARTIAL;
        }

        return Invoice::STATUS_PAID;
    }
}