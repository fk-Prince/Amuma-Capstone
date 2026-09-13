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

                        $isMedical = $scheduleService->service_id !== null;

                        if ($isMedical) {
                            $category = 'medical';
                            $durationMinutes = null;

                            $maxDuration = $scheduleService->service?->maximum_duration;

                            if ($maxDuration) {
                                [$hours, $minutes, $seconds] = array_pad(explode(':', $maxDuration), 3, 0);
                                $durationMinutes = ((int) $hours * 60) + (int) $minutes + ((int) $seconds / 60);
                            }
                        } else {
                            $category = 'adl';
                            $durationMinutes = $scheduleService->hours_booked !== null
                                ? ((float) $scheduleService->hours_booked) * 60
                                : null;
                        }

                        $onlineSessions = ($scheduleService->assigned ?? collect())
                            ->flatMap(fn($sibling) => $sibling->onlineSchedules ?? collect());

                        $workedMinutes = $onlineSessions
                            ->filter(fn($session) => $session->in_timestamp && $session->out_timestamp)
                            ->sum(fn($session) => Carbon::parse($session->in_timestamp)
                                ->diffInMinutes(Carbon::parse($session->out_timestamp)));

                        $latestInTimestamp = $onlineSessions
                            ->filter(fn($session) => $session->in_timestamp)
                            ->sortByDesc('in_timestamp')
                            ->first()?->in_timestamp;

                        $activeSession = $onlineSessions
                            ->first(fn($session) => $session->in_timestamp && !$session->out_timestamp);

                        $estimatedEnd = null;
                        $remainingMinutes = $durationMinutes;

                        if ($durationMinutes !== null) {
                            if ($activeSession) {
                                $remainingMinutes = max(round($durationMinutes - $workedMinutes), 0);
                                $anchor = Carbon::parse($activeSession->in_timestamp)->max(Carbon::parse($schedule->scheduled_at));
                                $end = $anchor->copy()->addMinutes($remainingMinutes);
                            } elseif ($latestInTimestamp) {
                                $remainingMinutes = max(round($durationMinutes - $workedMinutes), 0);
                                $anchor = Carbon::now()->max(Carbon::parse($schedule->scheduled_at));
                                $end = $anchor->copy()->addMinutes($remainingMinutes);
                            } else {
                                $end = Carbon::parse($schedule->scheduled_at)->addMinutes($durationMinutes);
                            }

                            if ($end->second >= 30) {
                                $end->addMinute();
                            }
                            $end->second(0);

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
                        ];
                    })
                    ->filter()
                    ->values();
            }),
        ];
    }
}
