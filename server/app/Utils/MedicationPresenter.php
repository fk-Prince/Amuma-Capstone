<?php

namespace App\Utils;

use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\Vital;

class MedicationPresenter
{
    public static function medication(Medication $medication): array
    {
        return [
            'id' => (string) $medication->medication_id,
            'category' => 'Medication',
            'name' => $medication->name,
            'strength' => $medication->strength,
            'dosageAmount' => (string) $medication->dosage_amount,
            'dosageUnit' => $medication->dosage_unit,
            'route' => $medication->route,
            'instructions' => $medication->instructions,
            'takenFor' => $medication->taken_for ?? '',
            'duration' => $medication->duration,
            'frequency' => $medication->frequency,
            'kind' => $medication->kind,
            'times' => $medication->times ?? [],
            'startDate' => $medication->start_date?->format('Y-m-d'),
            'recorded_date' => $medication->recorded_at?->toISOString(),
            'schedules' => $medication->schedules
                ->map(fn($schedule) => self::schedule($schedule))
                ->values(),
        ];
    }

    public static function schedule(MedicationSchedule $schedule): array
    {
        return [
            'id' => (string) $schedule->medication_schedule_id,
            'date' => $schedule->date?->format('Y-m-d'),
            'time' => $schedule->time,
            'status' => $schedule->status,
            'marked_by' => $schedule->marked_by,
        ];
    }

    public static function vital(Vital $vital): array
    {
        return [
            'id' => (string) $vital->vital_id,
            'category' => 'Vital Signs',
            'bloodPressureSystolic' => $vital->blood_pressure_systolic !== null ? (string) $vital->blood_pressure_systolic : '',
            'bloodPressureDiastolic' => $vital->blood_pressure_diastolic !== null ? (string) $vital->blood_pressure_diastolic : '',
            'heartRate' => $vital->heart_rate !== null ? (string) $vital->heart_rate : '',
            'respiratoryRate' => $vital->respiratory_rate !== null ? (string) $vital->respiratory_rate : '',
            'temperature' => $vital->temperature !== null ? (string) $vital->temperature : '',
            'oxygenSaturation' => $vital->oxygen_saturation !== null ? (string) $vital->oxygen_saturation : '',
            'bloodGlucose' => $vital->blood_glucose !== null ? (string) $vital->blood_glucose : '',
            'painLevel' => $vital->pain_level !== null ? (string) $vital->pain_level : '',
            'recordedDate' => $vital->recorded_date?->format('Y-m-d'),
            'recordedTime' => $vital->recorded_time,
            'notes' => $vital->notes ?? '',
        ];
    }
}
