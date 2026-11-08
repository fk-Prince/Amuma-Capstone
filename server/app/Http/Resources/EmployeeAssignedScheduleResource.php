<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class EmployeeAssignedScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $employee = $this->employees;

        return [
            'employee_id' => $this->employee_id,
            'full_name' => $employee?->full_name,
            'avatar' => $employee?->avatar,
            'email' => $employee?->users?->email,
            'role_name' => ucwords(str_replace('_', ' ', $this->role_name)),
            'shifts' => $this->caregiverShifts
                ->map(fn($shift) => [
                    'caregiver_shift_id' => $shift->caregiver_shift_id,
                    'start_time' => substr((string) $shift->start_time, 0, 5),
                    'end_time' => substr((string) $shift->end_time, 0, 5),
                    'note' => $shift->note,
                    'resident_name' => $shift->admission?->patient?->display_name ?? '',
                    'room_no' => $shift->admission?->bed?->room?->room_no,
                    'bed_no' => $shift->admission?->bed?->bed_no,
                    'admitted_at' => $shift->admission?->admitted_at?->toISOString(),
                    'discharged_at' => $shift->admission?->discharged_at?->toISOString(),
                ])
                ->values(),
            'schedules' => $this->scheduleAssignments
                ->groupBy(fn($assignment) => $assignment->scheduleService->schedule_id)
                ->map(fn(Collection $assignments) => $this->schedule($assignments))
                ->sortBy('scheduled_at')
                ->values(),
        ];
    }

    private function schedule(Collection $assignments): array
    {
        $services = $assignments->pluck('scheduleService');
        $schedule = $services->first()->schedule;
        $patient = $schedule->patient;
        $window = $assignments->first(fn($assignment) => $assignment->start_time && $assignment->end_time);

        $minutes = (int) $services->sum(fn($service) => $this->durationMinutes($service));
        $isAdl = $services->contains(fn($service) => $service->duration !== null);

        return [
            'timed_out_at' => $isAdl ? null : $this->timedOutAt($assignments),
            'schedule_id' => $schedule->schedule_id,
            'schedule_code' => $schedule->schedule_code,
            'status' => $schedule->status,
            'category' => $schedule->category,
            'type' => $isAdl ? 'adl' : 'medical',
            'patient_name' => $patient?->display_name,
            'services' => $services
                ->map(fn($service) => $service->service?->service_name ?? 'ADL')
                ->unique()
                ->values(),
            'scheduled_at' => $schedule->scheduled_at?->toISOString(),
            'ends_at' => $schedule->scheduled_at && $minutes
                ? $schedule->scheduled_at->copy()->addMinutes($minutes)->toISOString()
                : null,
            'duration_minutes' => $minutes,
            'start_time' => $window ? substr($window->start_time, 0, 5) : null,
            'end_time' => $window ? substr($window->end_time, 0, 5) : null,
            'note' => ($window ?? $assignments->first())->note,
        ];
    }

    private function timedOutAt(Collection $assignments): ?string
    {
        $rows = $assignments
            ->filter(fn($assignment) => $assignment->relationLoaded('attendances'))
            ->flatMap(fn($assignment) => $assignment->attendances)
            ->filter(fn($row) => $row->in_timestamp !== null);

        if ($rows->isEmpty() || $rows->contains(fn($row) => $row->out_timestamp === null)) {
            return null;
        }

        return $rows->max('out_timestamp')?->toISOString();
    }

    private function durationMinutes($service): int
    {
        if ($service->duration !== null) {
            return (int) round(((float) $service->duration) * 60);
        }

        $maxDuration = $service->service?->maximum_duration;

        if (!$maxDuration) {
            return 0;
        }

        [$hours, $minutes] = array_pad(explode(':', (string) $maxDuration), 2, 0);

        return ((int) $hours * 60) + (int) $minutes;
    }
}
