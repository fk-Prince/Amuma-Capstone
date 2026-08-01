<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalMinutes = $this->scheduleServices->sum(
            fn($scheduleService) => $this->resolveDurationMinutes($scheduleService)
        );

        $totalHours = round($totalMinutes / 60, 2);

        $startTime = $this->scheduled_at;

        $endTime = $startTime && $totalMinutes
            ? $startTime->copy()->addMinutes($totalMinutes)
            : null;

        return [
            'schedule_id' => $this->schedule_id,
            'schedule_code' => $this->schedule_code,
            'status' => $this->status,
            'category' => $this->category,

            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'scheduled_date' => $this->scheduled_at?->format('Y-m-d'),

            'start_time' => $startTime?->format('g:i A'),
            'end_time' => $endTime?->format('g:i A'),

            'total_duration_minutes' => $totalMinutes,
            'total_hours' => (float) $totalHours,

            'type' => $this->scheduleServices->contains(
                fn($service) => $service->hours_booked !== null
            )
                ? 'adl'
                : 'medical',

            'patient' => $this->whenLoaded('patient', function () {

                $location = $this->patient->relationLoaded('location')
                    ? $this->patient->location
                    : $this->patient->location()->first();

                return [
                    'patient_id' => $this->patient->patient_id,

                    'full_name' => trim(
                        "{$this->patient->first_name} {$this->patient->last_name}"
                    ),

                    'address' => $location?->full_address,
                ];
            }),

            'services' => $this->whenLoaded(
                'scheduleServices',
                fn() => $this->scheduleServices->map(function ($scheduleService) {
                    return [
                        'schedule_services_id' => $scheduleService->schedule_services_id,
                        'service_id' => $scheduleService->service_id,
                        'service_name' => $scheduleService->service?->service_name,

                        'hours_booked' => $scheduleService->hours_booked !== null
                            ? (float) $scheduleService->hours_booked
                            : null,

                        'duration_minutes' => $this->resolveDurationMinutes($scheduleService),

                        'status' => $scheduleService->status,
                        'type' => $scheduleService->type,

                        'assignees' => $scheduleService->relationLoaded('assigned')
                            ? $scheduleService->assigned->map(function ($assignment) {

                                $employee = $assignment->employee?->employees;

                                return [
                                    'employee_id' => $assignment->employee_id,
                                    'full_name' => $employee
                                        ? trim(
                                            "{$employee->first_name} {$employee->last_name}"
                                        )
                                        : null,
                                    'avatar' => $employee?->avatar,
                                    'role' => $assignment->role,
                                    'online' => $assignment->relationLoaded('onlineSchedules')
                                        ? $assignment->onlineSchedules->map(function ($online) {
                                            return [
                                                'qr_in' => $online->qr_in,
                                                'qr_out' => $online->qr_out,
                                                'in_timestamp' => $online->in_timestamp?->toISOString(),
                                                'out_timestamp' => $online->out_timestamp?->toISOString(),
                                                'notes' => $online->notes,
                                            ];
                                        })->values()
                                        : [],
                                ];
                            })->values()
                            : [],
                    ];
                })->values()
            ),
        ];
    }


    private function resolveDurationMinutes($scheduleService): int
    {
        if ($scheduleService->hours_booked !== null) {
            return (int) round(
                ((float) $scheduleService->hours_booked) * 60
            );
        }

        $maxDuration = $scheduleService->service?->maximum_duration;

        if (!$maxDuration) {
            return 0;
        }

        if ($maxDuration instanceof \Carbon\CarbonInterface) {
            return ($maxDuration->hour * 60)
                + $maxDuration->minute;
        }

        [$hours, $minutes] = array_pad(
            explode(':', (string) $maxDuration),
            2,
            0
        );

        return ((int) $hours * 60)
            + (int) $minutes;
    }
}
