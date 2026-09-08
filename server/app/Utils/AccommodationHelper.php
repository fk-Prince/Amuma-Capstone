<?php

namespace App\Utils;

use App\Models\AdmissionPeriod;
use App\Models\Invoice;
use App\Models\PatientAdmission;
use Carbon\Carbon;

class AccommodationHelper
{
    public static function settle(AdmissionPeriod $period): void
    {
        $outstanding = $period->invoiceAdmissionLines()
            ->with('invoice.allocations.refundAllocations.refund', 'invoice.invoiceAdjustments')
            ->get()
            ->map(fn($line) => $line->invoice)
            ->filter()
            ->unique('invoice_id')
            ->sum(fn(Invoice $invoice) => $invoice->balance_due);

        if ($outstanding > 0) {
            return;
        }

        $period->update([
            'status' => AdmissionPeriod::STATUS_ACTIVE,
        ]);
    }

    public static function supersede(AdmissionPeriod $period): void
    {
        $period->update([
            'status' => AdmissionPeriod::STATUS_INACTIVE,
        ]);
    }

    public static function activate(Invoice $invoice): void
    {
        if ($invoice->balance_due > 0) {
            return;
        }

        $invoice->loadMissing('invoiceAdmissionLines.admissionPeriod');

        $invoice->invoiceAdmissionLines
            ->map(fn($line) => $line->admissionPeriod)
            ->filter()
            ->unique('admission_period_id')
            ->each(fn(AdmissionPeriod $period) => static::settle($period));
    }


    public static function deactivate(PatientAdmission $admission, ?Carbon $at = null)
    {
        $at = $at ?? Carbon::now();

        $periods = AdmissionPeriod::query()
            ->where('patient_admission_id', $admission->patient_admission_id)
            ->whereNotIn('status', AdmissionPeriod::CLOSED_STATUSES)
            ->get();

        foreach ($periods as $period) {
            $start = Carbon::parse($period->start_date);
            $end = Carbon::parse($period->end_date);

            $period->update([
                'status' => AdmissionPeriod::STATUS_INACTIVE,
                'end_date' => match (true) {
                    $at->lessThanOrEqualTo($start) => $start,
                    $at->lessThan($end) => $at,
                    default => $end,
                },
            ]);
        }
    }
}
