<?php

namespace App\Service;

use App\Models\AdmissionPeriod;
use App\Models\Bed;
use App\Models\PatientAdmission;
use App\Models\RoomTransfer;
use Carbon\Carbon;


class RoomTransferService
{
    public function currentBedId(PatientAdmission $admission)
    {
        return $admission->bed_id;
    }

    public function move(PatientAdmission $admission, int $toBedId, string $type,  ?string $reason = null)
    {
        $fromBed = $admission->bed_id ? Bed::find($admission->bed_id) : null;
        $toBed = Bed::find($toBedId);

        $admission->update(['bed_id' => $toBedId]);

        if (!$fromBed || !$toBed || (int) $fromBed->bed_id === (int) $toBedId) {
            return null;
        }

        return RoomTransfer::create([
            'patient_admission_id' => $admission->patient_admission_id,
            'from_room_id'         => $fromBed->room_id,
            'to_room_id'           => $toBed->room_id,
            'from_bed_id'          => $fromBed->bed_id,
            'to_bed_id'            => $toBed->bed_id,
            'type'                 => $type,
            'transfer_date'        => Carbon::now(),
            'reason'               => $reason,
        ]);
    }

    public function history(int $admissionId)
    {
        $periods = AdmissionPeriod::with('branchContract')
            ->where('patient_admission_id', $admissionId)
            ->orderBy('admission_period_id')
            ->get();


        $indexOn = function ($at) use ($periods) {
            $index = $periods->search(
                fn($period) => $period->start_date > $at
            );

            return $index === false
                ? $periods->count() - 1
                : max(0, $index - 1);
        };

        $contractAt = fn(int $index) => $periods->get($index)?->branchContract;

        return RoomTransfer::with(['fromRoom', 'toRoom', 'fromBed', 'toBed'])
            ->where('patient_admission_id', $admissionId)
            ->orderByDesc('room_transfer_id')
            ->get()
            ->map(fn($transfer) => [
                'room_transfer_id'     => $transfer->room_transfer_id,
                'patient_admission_id' => $transfer->patient_admission_id,
                'from_room'            => $transfer->fromRoom,
                'to_room'              => $transfer->toRoom,
                'from_bed'             => $transfer->fromBed,
                'to_bed'               => $transfer->toBed,
                'reason'               => $transfer->reason,
                'type'                 => $transfer->type,
                'accommodation_change' => $transfer->type
                    === RoomTransfer::TYPE_ACCOMMODATION_CHANGE,
                'from_contract'        => $contractAt(
                    $transfer->type === RoomTransfer::TYPE_ACCOMMODATION_CHANGE
                        ? max(0, $indexOn($transfer->transfer_date) - 1)
                        : $indexOn($transfer->transfer_date)
                ),
                'to_contract'          => $contractAt($indexOn($transfer->transfer_date)),
                'created_at'           => $transfer->transfer_date,
            ])
            ->values();
    }
}
