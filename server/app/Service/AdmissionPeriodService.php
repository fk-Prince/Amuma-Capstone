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


    /*
        An accommodation change is flat: moving up charges only the room price
        still missing, moving down or sideways costs nothing and refunds
        nothing. The diagnosis fee billed with the admission stays as charged.
    */
    public function upgradeFee(float $newPrice, float $roomCharged): float
    {
        return round(max(0, $newPrice - $roomCharged), 2);
    }

    public function moveBilling(AdmissionPeriod $period, AdmissionPeriod $next, BranchContract $newContract, array &$upgrades): void
    {
        $fee = $this->upgradeFee((float) $newContract->price, $period->roomChargedInChain());

        $origin = $period->invoiceAdmissionLines()
            ->with('invoice')
            ->orderBy('invoice_admission_id')
            ->first()
            ?->invoice;

        if (!$origin) {
            return;
        }

        $description = $this->changeDescription($period->branchContract, $newContract);

        if ($fee >= 0.01 && $this->settled($origin)) {
            $this->addUpgrade($upgrades, $origin, $next, $fee, $description);

            return;
        }

        if ($fee >= 0.01) {
            InvoiceAdjustment::create([
                'invoice_id' => $origin->invoice_id,
                'type'       => 'correction',
                'amount'     => $fee,
                'reason'     => 'Accommodation upgraded. Difference to the new plan.',
            ]);
        }

        // A downgrade still records its line, at no charge, so the new
        // accommodation shows up on the invoice and in the period chain.
        $origin->invoiceAdmissionLines()->create([
            'admission_period_id' => $next->admission_period_id,
            'price'               => $fee,
            'description'         => $description,
        ]);

        $origin->refresh();
        $origin->syncStatus();
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

            $oldContract = $future->branchContract;

            $future->update([
                'branch_contract_id' => $contract->branch_contract_id,
            ]);

            $roomLines = $future->invoiceAdmissionLines
                ->sortBy('invoice_admission_id')
                ->values();

            $line = $roomLines->first();

            if (!$line) {
                continue;
            }

            $line->update([
                'description' => $this->carriedDescription($future, $oldContract, $contract, $line->description),
            ]);

            $fee = $this->upgradeFee((float) $contract->price, (float) $roomLines->sum('price'));

            if ($fee < 0.01) {
                continue;
            }

            $invoice = $line->invoice;

            if (!$invoice) {
                continue;
            }

            if ($this->settled($invoice)) {
                $this->addUpgrade($upgrades, $invoice, $future, $fee, $this->changeDescription($oldContract, $contract));

                continue;
            }

            $line->update([
                'price' => round((float) $line->price + $fee, 2),
            ]);

            InvoiceAdjustment::create([
                'invoice_id' => $invoice->invoice_id,
                'type'       => 'correction',
                'amount'     => $fee,
                'reason'     => 'Accommodation upgraded. Prepaid period charged the difference to the new plan.',
            ]);

            $invoice->syncStatus();
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
