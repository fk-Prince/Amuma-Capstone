<?php

namespace App\Utils;

use App\Models\PatientActivity;


class PatientActivityPresenter
{
    public static function patientActivity(PatientActivity $activity): array
    {
        return [
            'id' => (string) $activity->patient_activity_id,
            'title' => $activity->title,
            'subtitle' => $activity->subtitle ?? '',
            'description' => $activity->description ?? '',
            'type' => $activity->type,
            'occurredAt' => $activity->occurred_at?->toISOString(),
        ];
    }
}
