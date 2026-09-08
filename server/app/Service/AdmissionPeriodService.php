<?php

namespace App\Service;

use App\Models\AdmissionPeriod;
use App\Models\BranchContract;
use App\Models\Invoice;
use App\Models\InvoiceAdjustment;
use App\Models\PatientAdmission;
use App\Models\Room;
use Carbon\Carbon;
use Exception;

class AdmissionPeriodService
{
    public function open(PatientAdmission $admission,   int $contractId,   string $reason,  Carbon $startDate,  Carbon $endDate, ?AdmissionPeriod $parent = null, ?string $note = null)
    {
        // The first period waits with the patient. Everything opened afterwards
        // — an extension, a room change — belongs to a stay already under way.
        $isPending = $reason === AdmissionPeriod::REASON_ADMITTED
            && $admission->status !== PatientAdmission::STATUS_ADMITTED;

        return AdmissionPeriod::create([
            'patient_admission_id'       => $admission->patient_admission_id,
            'branch_contract_id'         => $contractId,
            'start_date'                 => $startDate,
            'end_date'                   => $endDate,
            'status'                     => $isPending
                ? AdmissionPeriod::STATUS_PENDING
                : AdmissionPeriod::STATUS_ACTIVE,
            'reason'                     => $reason,
            'note'                       => $note,
            'parent_admission_period_id' => $parent?->admission_period_id,
        ]);
    }


    public function current(PatientAdmission $admission)
    {
        return $admission->periods()
            ->whereNotIn('status', AdmissionPeriod::CLOSED_STATUSES)
            ->orderByRaw(
                "CASE WHEN reason = ? THEN 1 ELSE 0 END",
                [AdmissionPeriod::REASON_EXTENDED]
            )
            ->orderBy('admission_period_id')
            ->first()
            ?? $admission->periods()
            ->orderByDesc('admission_period_id')
            ->first();
    }

    public function future(PatientAdmission $admission, AdmissionPeriod $current)
    {
        return $admission->periods()
            ->whereNotIn('status', AdmissionPeriod::CLOSED_STATUSES)
            ->where('admission_period_id', '>', $current->admission_period_id)
            ->with('invoiceAdmissionLines.invoice')
            ->orderBy('admission_period_id')
            ->get();
    }


    public function repriceConsumed(AdmissionPeriod $period,  float $oldPrice,  float $consumed)
    {
        $lines = $period->invoiceAdmissionLines;

        if ($oldPrice <= 0 || $lines->isEmpty()) {
            return;
        }

        $assigned = 0.0;
        $last = $lines->count() - 1;

        foreach ($lines->values() as $index => $line) {
            $share = $index === $last
                ? round($consumed - $assigned, 2)
                : round((float) $line->price / $oldPrice * $consumed, 2);

            $line->update(['price' => $share]);
            $assigned = round($assigned + $share, 2);
        }
    }


    public function carryForward(mixed $futurePeriods, BranchContract $newContract)
    {
        $newPrice = (float) $newContract->price;

        foreach ($futurePeriods as $future) {
            $future->update([
                'branch_contract_id' => $newContract->branch_contract_id,
            ]);

            $deltaByInvoice = $future->invoiceAdmissionLines
                ->groupBy('invoice_id')
                ->map(fn($lines) => round(
                    $lines->count() * $newPrice - (float) $lines->sum('price'),
                    2
                ));

            foreach ($future->invoiceAdmissionLines as $line) {
                $line->update(['price' => $newPrice]);
            }

            foreach ($deltaByInvoice as $invoiceId => $delta) {
                if (!$invoiceId || abs($delta) < 0.01) {
                    continue;
                }

                InvoiceAdjustment::create([
                    'invoice_id' => $invoiceId,
                    'type'       => 'correction',
                    'amount'     => $delta,
                    'reason'     => $delta > 0
                        ? 'Accommodation upgraded. Prepaid period re-priced to the new plan.'
                        : 'Accommodation downgraded. Prepaid period re-priced to the new plan.',
                ]);

                Invoice::find($invoiceId)?->syncStatus();
            }
        }
    }

    public function resolveContract(AdmissionPeriod $period,  Room $room,   array $payload)
    {
        $current = $period->branchContract;

        if (!empty($payload['contract_id'])) {
            $contract = BranchContract::find($payload['contract_id']);

            if (!$contract) {
                throw new Exception('Contract not found.', 404);
            }

            if ($contract->billing_cycle !== $current->billing_cycle) {
                throw new Exception(
                    'A room change keeps the current billing cycle. '
                        . "Use Extend Stay to move from {$current->billing_cycle} to {$contract->billing_cycle}.",
                    422
                );
            }

            return $contract;
        }

        $targetType = match (strtoupper(trim($room->room_type ?? ''))) {
            'VIP' => BranchContract::ACCOMMODATION_TYPE_VIP,
            'COMMON' => BranchContract::ACCOMMODATION_TYPE_COMMON,
            default => $current->accommodation_type,
        };

        if ($targetType === $current->accommodation_type) {
            return $current;
        }

        $contract = BranchContract::where('branch_id', $current->branch_id)
            ->where('category', $current->category)
            ->where('accommodation_type', $targetType)
            ->where('billing_cycle', $current->billing_cycle)
            ->where('is_active', true)
            ->first();

        if (!$contract) {
            throw new Exception(
                "No active {$current->category} {$targetType} {$current->billing_cycle} contract exists for this branch.",
                422
            );
        }

        return $contract;
    }
}
