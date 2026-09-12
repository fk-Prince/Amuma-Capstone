<?php

namespace App\Observers;

use App\Models\AdmissionPeriod;
use App\Models\Bed;
use App\Models\Invoice;
use App\Models\PatientAdmission;
use App\Models\Schedule;
use App\Utils\AdmissionHelper;
use Carbon\Carbon;
use Throwable;

class InvoiceObserver
{
    public function updated(Invoice $invoice): void
    {
        if (
            !$invoice->wasChanged('status')
            || $invoice->status !== Invoice::STATUS_VOID
        ) {
            return;
        }

        $at = Carbon::now();

        $this->cancelSchedules($invoice);
        $this->cancelAdmissionPeriods($invoice, $at);
    }

    private function cancelSchedules(Invoice $invoice): void
    {
        $invoice->loadMissing('invoiceServices.scheduleService.schedule');

        $invoice->invoiceServices
            ->map(fn($line) => $line->scheduleService?->schedule)
            ->filter()
            ->unique('schedule_id')
            ->reject(fn(Schedule $schedule) => $schedule->status === Schedule::STATUS_CANCELLED)
            ->each(fn(Schedule $schedule) => $schedule->update([
                'status' => Schedule::STATUS_CANCELLED,
            ]));
    }

    private function cancelAdmissionPeriods(Invoice $invoice, Carbon $at): void
    {
        $invoice->loadMissing('invoiceAdmissionLines.admissionPeriod');

        $periods = $invoice->invoiceAdmissionLines
            ->map(fn($line) => $line->admissionPeriod)
            ->filter()
            ->unique('admission_period_id')
            ->groupBy('patient_admission_id');

        foreach ($periods as $admissionId => $admissionPeriods) {
            $anchor = $this->earliestStart((int) $admissionId);

            foreach ($admissionPeriods as $period) {
                $this->closePeriod($period, $at);
            }

            $this->resequence((int) $admissionId, $anchor);
            $this->closeAdmission((int) $admissionId, $at);
        }
    }

    private function earliestStart(int $admissionId): ?Carbon
    {
        $start = AdmissionPeriod::query()
            ->where('patient_admission_id', $admissionId)
            ->whereNotIn('status', AdmissionPeriod::CLOSED_STATUSES)
            ->min('start_date');

        return $start ? Carbon::parse($start) : null;
    }


    private function resequence(int $admissionId, ?Carbon $anchor): void
    {
        if (!$anchor) {
            return;
        }

        $periods = AdmissionPeriod::query()
            ->where('patient_admission_id', $admissionId)
            ->whereNotIn('status', AdmissionPeriod::CLOSED_STATUSES)
            ->with('branchContract')
            ->orderBy('start_date')
            ->orderBy('admission_period_id')
            ->get();

        $cursor = $anchor->copy();

        foreach ($periods as $period) {
            $start = Carbon::parse($period->start_date);
            if ($start->equalTo($cursor)) {
                $cursor = Carbon::parse($period->end_date);
                continue;
            }
            $end = $this->periodEnd($period, $cursor);
            $period->update([
                'start_date' => $cursor->copy(),
                'end_date' => $end,
            ]);
            $cursor = $end->copy();
        }
    }

    private function periodEnd(AdmissionPeriod $period, Carbon $start): Carbon
    {
        $originalStart = Carbon::parse($period->start_date);
        $originalEnd = Carbon::parse($period->end_date);
        $cycle = $period->branchContract?->billing_cycle;

        if ($cycle) {
            try {
                $isWholeCycle = $originalEnd->equalTo(
                    AdmissionHelper::calculateEndDate($originalStart->copy(), $cycle)
                );

                if ($isWholeCycle) {
                    return AdmissionHelper::calculateEndDate($start->copy(), $cycle);
                }
            } catch (Throwable) {
            }
        }

        return $start->copy()->addSeconds(
            (int) $originalStart->diffInSeconds($originalEnd)
        );
    }


    private function closePeriod(AdmissionPeriod $period, Carbon $at): void
    {
        $start = Carbon::parse($period->start_date);
        $end = $period->end_date ? Carbon::parse($period->end_date) : null;

        $period->update([
            'status' => AdmissionPeriod::STATUS_CANCELLED,
            'end_date' => match (true) {
                $at->lessThanOrEqualTo($start) => $start,
                $end === null || $at->lessThan($end) => $at,
                default => $end,
            },
        ]);
    }



    private function closeAdmission(int $admissionId, Carbon $at): void
    {
        $admission = PatientAdmission::find($admissionId);

        if (!$admission || $admission->status === PatientAdmission::STATUS_DISCHARGED) {
            return;
        }

        $coverageEnd = $admission->periods()
            ->whereNotIn('status', AdmissionPeriod::CLOSED_STATUSES)
            ->max('end_date');

        if ($coverageEnd) {
            $admission->update(['discharged_at' => $coverageEnd]);

            return;
        }

        $admission->update([
            'status' => PatientAdmission::STATUS_DISCHARGED,
            'discharged_at' => $at,
        ]);

        if ($admission->bed_id) {
            Bed::where('bed_id', $admission->bed_id)->update([
                'status' => Bed::STATUS_AVAILABLE,
            ]);
        }
    }
}
