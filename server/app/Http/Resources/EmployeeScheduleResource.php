<?php

namespace App\Http\Resources;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $branchInfo = $this->employeeBranch->first();
        return [
            'employee_id' => $this->employee_id,
            'uuid' => $this->users->uuid ?? null,
            'email' => $this->users->email ?? null,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name ?? null,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'avatar' => $this->avatar,
            'achievement_titles' => collect($this->achievements ?? [])
                ->pluck('label')
                ->filter()
                ->values(),
            'location' => $this->locations,
            'birth_date' => $this->birth_date,
            'phone_number' => $this->phone_number,
            'role_name' => $branchInfo?->role_name
                ? ucwords(str_replace('_', ' ', $branchInfo->role_name))
                : null,
            'assignment_type' => $branchInfo?->assignment_type,
            'formatted_assignment_type' => match ($branchInfo?->assignment_type) {
                'both' => 'Homecare + Inhouse Facility',
                'online' => 'Homecare',
                'facility' => 'Inhouse Facility',
                default => 'Not yet Assigned',
            },
            'status' => $this->status,
            'hired_date' => $this->created_at,
            'is_busy' => $this->is_busy,
            'conflict_count' => $this->conflict_count,
            'is_assigned' => $this->is_assigned,
            'conflict_schedules' => $this->whenLoaded('employeeBranch', function () {
                return $this->employeeBranch
                    ->flatMap(fn($branch) => $branch->scheduleAssignments ?? collect())
                    ->filter(fn($assignment) => $assignment->is_active)
                    ->map(function ($assignment) {
                        $scheduleService = $assignment->scheduleService;
                        $schedule = $scheduleService?->schedule;

                        if (!$schedule) {
                            return null;
                        }

                        if (!in_array($schedule->status, [Schedule::STATUS_ONGOING, Schedule::STATUS_PENDING], true)) {
                            return null;
                        }

                        [$category, $durationMinutes] = $this->categoryAndDuration($scheduleService);

                        $estimatedEnd = null;
                        $remainingMinutes = $durationMinutes;

                        if ($durationMinutes !== null) {
                            $start = Carbon::parse($schedule->scheduled_at);
                            $end = $start->copy()->addMinutes($durationMinutes);

                            $remainingMinutes = $start->isFuture()
                                ? round($durationMinutes)
                                : max(round(Carbon::now()->diffInMinutes($end, false)), 0);

                            $estimatedEnd = $end->toISOString();
                        }

                        return [
                            'schedule_code' => $schedule->schedule_code,
                            'scheduled_at' => $schedule->scheduled_at,
                            'status' => $schedule->status,
                            'category' => $category,
                            'duration_minutes' => $durationMinutes,
                            'remaining_minutes' => $remainingMinutes,
                            'estimated_end' => $estimatedEnd,
                            'checked_in_at' => $this->openCheckIn($assignment),
                        ];
                    })
                    ->filter()
                    ->values();
            }),
            'last_schedule' => $this->whenLoaded('lastAssignment', function ($assignment) {
                $scheduleService = $assignment->scheduleService;
                $schedule = $scheduleService?->schedule;

                if (!$schedule) {
                    return null;
                }

                [$category, $durationMinutes] = $this->categoryAndDuration($scheduleService);

                $lastTimeOut = $assignment->relationLoaded('attendances')
                    ? $assignment->attendances->whereNotNull('out_timestamp')->max('out_timestamp')
                    : null;

                return [
                    'last_time_out' => $lastTimeOut ? Carbon::parse($lastTimeOut)->toISOString() : null,
                    'schedule_code' => $schedule->schedule_code,
                    'scheduled_at' => $schedule->scheduled_at,
                    'status' => $schedule->status,
                    'category' => $category,
                    'service_name' => $scheduleService->service?->service_name,
                    'duration_minutes' => $durationMinutes,
                    'ends_at' => $durationMinutes !== null
                        ? Carbon::parse($schedule->scheduled_at)->addMinutes($durationMinutes)->toISOString()
                        : null,
                ];
            }),
        ];
    }

    private function openCheckIn($assignment): ?string
    {
        if (!$assignment->relationLoaded('attendances')) {
            return null;
        }

        $open = $assignment->attendances
            ->filter(fn($row) => $row->in_timestamp && !$row->out_timestamp)
            ->sortBy('in_timestamp')
            ->first();

        return $open ? Carbon::parse($open->in_timestamp)->toISOString() : null;
    }

    private function categoryAndDuration($scheduleService): array
    {
        if ($scheduleService->service_id === null) {
            return [
                'adl',
                $scheduleService->duration !== null ? ((float) $scheduleService->duration) * 60 : null,
            ];
        }

        $maxDuration = $scheduleService->service?->maximum_duration;

        if (!$maxDuration) {
            return ['medical', null];
        }

        [$hours, $minutes, $seconds] = array_pad(explode(':', $maxDuration), 3, 0);

        return ['medical', ((int) $hours * 60) + (int) $minutes + ((int) $seconds / 60)];
    }
}
