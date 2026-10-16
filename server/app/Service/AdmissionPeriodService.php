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
            ->where('admission_period_id', '!=', $current->admission_period_id)
            ->where('start_date', '>=', $current->end_date)
            ->with('invoiceAdmissionLines.invoice', 'branchContract')
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


    public function moveBilling(AdmissionPeriod $period, AdmissionPeriod $next, float $oldPrice, float $oldConsumed, float $newRemaining, BranchContract $newContract, array &$upgrades): void
    {
        $lines = $period->invoiceAdmissionLines->values();
        $before = $lines->map(fn($line) => (float) $line->price);
        $total = $before->sum();

        if ($lines->isEmpty() || $total <= 0) {
            return;
        }

        $this->repriceConsumed($period, $oldPrice, $oldConsumed);

        $kept = $lines
            ->map(fn($line, $index) => round($before[$index] - (float) $line->price, 2))
            ->all();

        $deltas = $this->allocateDelta($kept, round($newRemaining - array_sum($kept), 2));

        $perInvoice = [];

        foreach ($lines as $index => $line) {
            $entry = $perInvoice[$line->invoice_id]
                ?? ['invoice' => $line->invoice, 'kept' => 0.0, 'delta' => 0.0, 'description' => $line->description];

            $perInvoice[$line->invoice_id] = [
                'invoice' => $entry['invoice'],
                'description' => $entry['description'],
                'kept' => round($entry['kept'] + $kept[$index], 2),
                'delta' => round($entry['delta'] + $deltas[$index], 2),
            ];
        }

        foreach ($perInvoice as $entry) {
            $invoice = $entry['invoice'];
            $delta = $entry['delta'];
            $description = $this->changeDescription($period->branchContract, $newContract);
            $separate = $delta >= 0.01 && $this->settled($invoice);

            if ($separate) {
                $this->addUpgrade($upgrades, $invoice, $next, $delta, $description);
                $delta = 0.0;
            }

            if (abs($delta) >= 0.01) {
                InvoiceAdjustment::create([
                    'invoice_id' => $invoice->invoice_id,
                    'type'       => 'correction',
                    'amount'     => $delta,
                    'reason'     => $delta > 0
                        ? 'Accommodation upgraded mid-period. Difference for the remaining days.'
                        : 'Accommodation downgraded mid-period. Credit for the remaining days.',
                ]);
            }

            $price = round($entry['kept'] + $delta, 2);

            if ($price > 0) {
                $invoice->invoiceAdmissionLines()->create([
                    'admission_period_id' => $next->admission_period_id,
                    'price'               => $price,
                    'description'         => $separate ? $entry['description'] : $description,
                ]);
            }

            $invoice->refresh();
            $invoice->syncStatus();
        }
    }

    private function settled(Invoice $invoice): bool
    {
        $invoice->refresh();

        return $invoice->balance_due <= 0 && (float) $invoice->net_paid_amount > 0;
    }

    private function addUpgrade(array &$upgrades, Invoice $origin, AdmissionPeriod $period, float $price, string $description): void
    {
        $upgrades[$origin->invoice_id]['invoice'] = $origin;
        $upgrades[$origin->invoice_id]['lines'][] = [
            'period' => $period,
            'price' => $price,
            'description' => $description,
        ];
    }

    public function issueUpgrades(array $upgrades): void
    {
        foreach ($upgrades as $entry) {
            $lines = collect($entry['lines'])->filter(fn($line) => $line['price'] >= 0.01);

            if ($lines->isEmpty()) {
                continue;
            }

            $invoice = Invoice::create([
                'branch_id'    => $entry['invoice']->branch_id,
                'total_amount' => round($lines->sum('price'), 2),
                'status'       => Invoice::STATUS_PENDING,
            ]);

            foreach ($lines as $line) {
                $invoice->invoiceAdmissionLines()->create([
                    'admission_period_id' => $line['period']->admission_period_id,
                    'price'               => round($line['price'], 2),
                    'description'         => $line['description'],
                ]);
            }
        }
    }

    private function allocateDelta(array $amounts, float $delta): array
    {
        $deltas = array_fill(0, count($amounts), 0.0);

        if (abs($delta) < 0.01) {
            return $deltas;
        }

        if ($delta > 0) {
            $total = array_sum($amounts);
            $last = count($amounts) - 1;
            $assigned = 0.0;

            foreach ($amounts as $index => $amount) {
                $deltas[$index] = $index === $last
                    ? round($delta - $assigned, 2)
                    : round($total > 0 ? $amount / $total * $delta : $delta / count($amounts), 2);

                $assigned = round($assigned + $deltas[$index], 2);
            }

            return $deltas;
        }

        $remaining = $delta;

        foreach (array_reverse(array_keys($amounts)) as $index) {
            $take = max($remaining, -$amounts[$index]);
            $deltas[$index] = round($take, 2);
            $remaining = round($remaining - $take, 2);

            if ($remaining >= 0) {
                break;
            }
        }

        return $deltas;
    }

    private function direction(?BranchContract $from, BranchContract $to): string
    {
        if (!$from || (float) $to->price === (float) $from->price) {
            return 'CHANGE';
        }

        return (float) $to->price > (float) $from->price ? 'UPGRADE' : 'DOWNGRADE';
    }

    private function changeDescription(?BranchContract $from, BranchContract $to): string
    {
        return collect([
            'ACCOMMODATION ' . $this->direction($from, $to),
            $from ? "{$from->accommodation_type} to {$to->accommodation_type}" : $to->accommodation_type,
            $to->billing_cycle,
        ])->implode(' - ');
    }

    private function carriedDescription(AdmissionPeriod $period, ?BranchContract $from, BranchContract $to, ?string $current): ?string
    {
        if (!$from || $from->accommodation_type === $to->accommodation_type) {
            return $current;
        }

        return $this->changeDescription($from, $to);
    }

    public function carryForward(mixed $futurePeriods, BranchContract $newContract, array &$upgrades): void
    {
        foreach ($futurePeriods as $future) {
            $futureCycle = $future->branchContract?->billing_cycle;

            $contract = $futureCycle === $newContract->billing_cycle
                ? $newContract
                : BranchContract::where('branch_id', $newContract->branch_id)
                ->where('category', $newContract->category)
                ->where('accommodation_type', $newContract->accommodation_type)
                ->where('billing_cycle', $futureCycle)
                ->first();

            if (!$contract) {
                continue;
            }

            $newPrice = (float) $contract->price;
            $oldContract = $future->branchContract;

            $future->update([
                'branch_contract_id' => $contract->branch_contract_id,
            ]);

            $lines = $future->invoiceAdmissionLines->values();
            $prices = $lines->map(fn($line) => (float) $line->price)->all();
            $deltas = $this->allocateDelta($prices, round($newPrice - array_sum($prices), 2));

            $groups = [];

            foreach ($lines as $index => $line) {
                $groups[$line->invoice_id][] = [$line, $deltas[$index]];
            }

            foreach ($groups as $invoiceId => $entries) {
                $delta = round(array_sum(array_column($entries, 1)), 2);
                $invoice = $invoiceId ? Invoice::find($invoiceId) : null;

                if ($invoice && $delta >= 0.01 && $this->settled($invoice)) {
                    $this->addUpgrade($upgrades, $invoice, $future, $delta, $this->changeDescription($oldContract, $contract));

                    continue;
                }

                foreach ($entries as [$line, $lineDelta]) {
                    $line->update([
                        'price' => round((float) $line->price + $lineDelta, 2),
                        'description' => $this->carriedDescription($future, $oldContract, $contract, $line->description),
                    ]);
                }

                if (!$invoiceId || abs($delta) < 0.01) {
                    continue;
                }

                $reason = $delta > 0
                    ? 'Accommodation upgraded. Prepaid period re-priced to the new plan.'
                    : 'Accommodation downgraded. Prepaid period re-priced to the new plan.';

                InvoiceAdjustment::create([
                    'invoice_id' => $invoiceId,
                    'type'       => 'correction',
                    'amount'     => $delta,
                    'reason'     => $reason,
                ]);

                $invoice->syncStatus();
            }
        }
    }

    public function sameAccommodation(AdmissionPeriod $period, BranchContract $chosen): BranchContract
    {
        $current = $period->branchContract;

        if (!$current || $current->accommodation_type === $chosen->accommodation_type) {
            return $chosen;
        }

        $contract = BranchContract::where('branch_id', $current->branch_id)
            ->where('category', $current->category)
            ->where('accommodation_type', $current->accommodation_type)
            ->where('billing_cycle', $chosen->billing_cycle)
            ->where('is_active', true)
            ->first();

        if (!$contract) {
            throw new Exception(
                "No active {$current->accommodation_type} {$chosen->billing_cycle} contract exists for this branch.",
                422
            );
        }

        return $contract;
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
