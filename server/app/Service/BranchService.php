<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Models\Booking;
use App\Models\CaregiverShift;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\ScheduleAssigned;
use App\Models\PatientAdmission;
use App\Repository\BookingRepository;
use App\Repository\BranchContractRepository;
use App\Repository\BranchRepository;
use App\Repository\RoomRepository;
use App\Http\Resources\BranchResource;
use App\Models\BranchImage;
use App\Models\User;
use App\Service\External\SupabaseService;
use App\Service\Geo\IpGeolocationService;
use App\Service\Geo\NominatimService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BranchService
{

    public function __construct(
        private BranchRepository $branchRepository,
        private NominatimService $nomaticeService,
        private IpGeolocationService $ipGeolocation,
        private RoomRepository $roomRepository,
        private BookingRepository $bookingRepository,
        private BranchContractRepository $branchContractRepository,
        private AuditService $auditService,
    ) {}

    public function getBranch(string $uuid)
    {
        $branch = $this->branchRepository->getBranch($uuid);
        return new BranchResource($branch);
    }

    public function getHealthProviders(string $uuid)
    {
        $staff = $this->branchRepository
            ->getBranchHealthProviders($uuid)
            ->filter(fn($row) => $row->employees)
            ->map(fn($row) => [
                'name' => trim("{$row->employees->first_name} {$row->employees->last_name}"),
                'avatar' => $row->employees->avatar,
                'role_name' => ucwords(str_replace('_', ' ', $row->role_name)),
                'achievements' => collect($row->employees->achievements ?? [])
                    ->map(fn($item) => Arr::only($item, ['label', 'url', 'date']))
                    ->values(),
            ])
            ->values();

        return response()->json(['data' => $staff]);
    }

    public function getFeaturedBranches(array $payload)
    {
        $branch = $this->branchRepository->getHighestReviewPaginate($payload['per_page']);
        return BranchResource::collection($branch);
    }

    public function getBranchesByFilter(array $payload)
    {
        $city = null;
        if (!empty($payload['location'])) {
            $city = $payload['location'];
        } elseif (!empty($payload['lat']) && !empty($payload['long'])) {
            $city = $this->nomaticeService->getCityByCoords($payload['lat'], $payload['long']);
        }
        // else {
        //     $position = Location::get('8.8.8.8'); // TEST IP
        //     // // $position = Location::get(request()->ip()); // REAL IP
        //     // $city = $position?->cityName;
        //     $city = "Davao City";
        // }
        $filters = [
            'city' => $city,
            'provider_name' => $payload['provider_name'],
            'plan_code' => $payload['plan_code']
        ];

        $branch = $this->branchRepository->getFilterBranches(
            $payload['per_page'],
            $filters,
            $payload['sort'] ?? 'recommended',
            !empty($payload['lat']) ? (float) $payload['lat'] : null,
            !empty($payload['long']) ? (float) $payload['long'] : null,
            $this->viewerOrigin($payload['ip'] ?? null),
        );

        return BranchResource::collection($branch);
    }

    private function viewerOrigin(?string $ip): ?array
    {
        if (!$ip) {
            return null;
        }

        try {
            $position = Cache::remember(
                "ip-locate:{$ip}",
                now()->addHour(),
                fn() => $this->ipGeolocation->locate($ip)
            );
        } catch (\Throwable $e) {
            report($e);
            return null;
        }

        return $position ? [(float) $position['lat'], (float) $position['lng']] : null;
    }



    public function updateBranch(array $payload)
    {

        return DB::transaction(function () use ($payload) {
            $branch = $payload['branch'];
            if (
                isset($payload['image']) &&
                $payload['image'] instanceof UploadedFile
            ) {
                $image = SupabaseService::store($payload['image']);
            }

            $branch->update([
                'name' => $payload['name'],
                'description' => $payload['description'],
                'contact_number' => $payload['contact_number'] ?? null,
                'image' => $image['url'] ?? $payload['image'] ?? null,
                'email' => $payload['email'] ?? null
            ]);

            $oldAddress = $branch->location?->only(AuditService::ADDRESS_FIELDS) ?? [];

            $branch->location()->updateOrCreate(
                [],
                [
                    'street' => $payload['location']['street'],
                    'city' => $payload['location']['city'],
                    'province' => $payload['location']['province'],
                    'country' => $payload['location']['country'],
                    'full_address' => $payload['location']['full_address'] ?? null,
                    'longitude' => $payload['location']['longitude'] ?? null,
                    'latitude' => $payload['location']['latitude'] ?? null,
                ]
            );

            $this->auditService->addressChange(
                $branch,
                'branch_settings',
                $oldAddress,
                array_merge(array_fill_keys(AuditService::ADDRESS_FIELDS, null), $payload['location']),
                $branch->branch_id
            );

            return response()->json([
                'message' => 'Branch Information Successfully Updated.'
            ], 200);
        });
    }

    public function action(array $payload)
    {

        if ($payload['action'] === 'image') {

            if (empty($payload['image'])) {
                throw new \InvalidArgumentException('Image is required.');
            }
            $stored = SupabaseService::store($payload['image']);

            if (empty($stored['url'])) {
                throw new \RuntimeException('Image upload failed.');
            }

            // A branch only ever has one cover photo: a new upload replaces the old one.
            if (($payload['type'] ?? null) === BranchImage::IMAGE_COVER) {
                BranchImage::where('branch_id', $payload['branch_id'])
                    ->where('type', BranchImage::IMAGE_COVER)
                    ->delete();
            }

            $isPrivate = ($payload['visibility'] ?? null) === BranchImage::VISIBILITY_PRIVATE
                && ($payload['type'] ?? null) !== BranchImage::IMAGE_COVER;

            $data = BranchImage::create([
                'image_url' => $stored['url'],
                'branch_id' => $payload['branch_id'],
                'type' => $payload['type'],
                'visibility' => $isPrivate
                    ? BranchImage::VISIBILITY_PRIVATE
                    : BranchImage::VISIBILITY_PUBLIC,
                'description' => $payload['description'] ?? '',
            ]);

            return response()->json([
                'message' => 'Branch Image have been successfully added.',
                'data' => $data
            ], 200);
        }
    }

    public function retrieveAction(array $payload)
    {
        if ($payload['action'] === 'image') {
            $perPage = $payload['per_page'] ?? 20;
            $query = BranchImage::where('branch_id', $payload['branch_id']);

            // The cover photo is managed from Branch Information, so it stays out of the
            // gallery list unless it is asked for explicitly.
            if (!empty($payload['type'])) {
                $query->where('type', $payload['type']);
            } else {
                $query->where('type', '!=', BranchImage::IMAGE_COVER);
            }

            return $query->latest()->paginate($perPage);
        }
    }


    public function updateSettings(array $payload)
    {
        $branch = $payload['branch'];

        $settingPayload = Arr::only($payload, [
            'reserved_walkin_slots',
            'enable_booking_pre_admission',
            'enable_booking_complete_admission',
            'requires_full_payment_on_admit',
            'complete_admission_booking_percent',
            'homecare_downpayment_percent',
            'minimum_adl_hours',
            'is_open',
            'time_zone',
            'opening',
            'closing',
            'currency',
        ]);

        foreach (
            ['enable_booking_pre_admission', 'enable_booking_complete_admission', 'requires_full_payment_on_admit', 'is_open']
            as $key
        ) {
            if (array_key_exists($key, $settingPayload)) {
                $settingPayload[$key] = filter_var(
                    $settingPayload[$key],
                    FILTER_VALIDATE_BOOLEAN
                );
            }
        }

        foreach (['reserved_walkin_slots', 'minimum_adl_hours'] as $key) {
            if (array_key_exists($key, $settingPayload)) {
                $settingPayload[$key] = (int) $settingPayload[$key];
            }
        }

        foreach (['complete_admission_booking_percent', 'homecare_downpayment_percent'] as $key) {
            if (array_key_exists($key, $settingPayload)) {
                $settingPayload[$key] = min(100, max(1, (int) $settingPayload[$key]));
            }
        }

        $branch->update([
            'settings' => array_merge($branch->settings ?? [], $settingPayload),
        ]);

        return [
            'message' => 'Branch settings updated successfully.',
        ];
    }

    public function dashboard(array $payload): array
    {
        $branchId = $payload['branch_id'];
        $now = Carbon::now();

        $admissionsForBranch = fn() => PatientAdmission::whereHas(
            'bed.room',
            fn($q) => $q->where('branch_id', $branchId)
        );

        $admitted = $admissionsForBranch()
            ->where('status', PatientAdmission::STATUS_ADMITTED)
            ->count();

        $waiting = $admissionsForBranch()
            ->where('status', PatientAdmission::STATUS_WAITING)
            ->count();

        $newAdmissionsThisMonth = $admissionsForBranch()
            ->whereIn('status', [
                PatientAdmission::STATUS_ADMITTED,
                PatientAdmission::STATUS_DISCHARGED,
            ])
            ->whereBetween('admitted_at', [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ])
            ->count();

        $staffTotal = EmployeeBranch::where('branch_id', $branchId)
            ->where('status', '!=', EmployeeBranch::STATUS_INACTIVE)
            ->distinct('employee_id')
            ->count('employee_id');

        $roomStats = $this->roomRepository->getRoomStats($branchId);
        $bookingOverview = $this->bookingRepository->overview($branchId);
        $contractStats = $this->branchContractRepository->dashboardStats($branchId);

        $recentActivity = Booking::where('branch_id', $branchId)
            ->latest()
            ->take(6)
            ->get()
            ->map(fn($booking) => [
                'type' => 'booking',
                'title' => "Booking {$booking->reference_id}",
                'subtitle' => ucfirst($booking->category) . ' booking',
                'status' => $booking->status,
                'date' => $booking->created_at,
            ]);

        return [
            'patients' => [
                'admitted' => $admitted,
                'waiting' => $waiting,
                'new_this_month' => $newAdmissionsThisMonth,
            ],
            'staff' => [
                'total' => $staffTotal,
            ],
            'occupancy' => $roomStats,
            'bookings' => $bookingOverview['bookings'],
            'contracts' => $contractStats,
            'recent_activity' => $recentActivity,
            'my_duty' => $this->myDuty($branchId, $payload['viewer_user_id'] ?? null, $now),
        ];
    }

    private function serviceMinutes(object $service): int
    {
        if ($service->hours_booked !== null && (float) $service->hours_booked > 0) {
            return (int) round(((float) $service->hours_booked) * 60);
        }

        $maxDuration = $service->service?->maximum_duration;

        if ($maxDuration) {
            [$hours, $mins] = array_pad(explode(':', (string) $maxDuration), 2, 0);

            $total = ((int) $hours * 60) + (int) $mins;

            if ($total > 0) {
                return $total;
            }
        }

        return 30;
    }

    private function myDuty(int|string $branchId, int|string|null $userId, Carbon $now): ?array
    {
        if (!$userId) {
            return null;
        }

        $employee = Employee::where('user_id', $userId)->first();

        if (!$employee) {
            return null;
        }

        $link = EmployeeBranch::where('employee_id', $employee->employee_id)
            ->where('branch_id', $branchId)
            ->first();

        if (!$link || $link->status === EmployeeBranch::STATUS_INACTIVE || !in_array(strtolower((string) $link->role_name), ['caregiver', 'nurse'], true)) {
            return null;
        }

        $dayStart = $now->copy()->startOfDay();
        $dayEnd = $now->copy()->endOfDay();
        $blocks = collect();

        $shifts = CaregiverShift::where('caregiver_id', $employee->employee_id)
            ->where('is_active', true)
            ->whereHas('admission.bed.room', fn($q) => $q->where('branch_id', $branchId))
            ->with(['admission.patient', 'admission.bed.room'])
            ->get();

        foreach ($shifts as $shift) {
            foreach ([-1, 0] as $offset) {
                $start = $dayStart->copy()->addDays($offset)->setTimeFromTimeString((string) $shift->start_time);
                $end = $dayStart->copy()->addDays($offset)->setTimeFromTimeString((string) $shift->end_time);

                if ($end->lessThanOrEqualTo($start)) {
                    $end->addDay();
                }

                if ($end->lessThanOrEqualTo($dayStart) || $start->greaterThan($dayEnd)) {
                    continue;
                }

                $room = $shift->admission?->bed?->room;

                $blocks->push([
                    'type' => 'shift',
                    'setting' => 'facility',
                    'title' => 'Facility shift',
                    'patient_name' => $shift->admission?->patient?->display_name,
                    'location' => $room?->room_no
                        ? 'Room ' . $room->room_no . ($shift->admission?->bed?->bed_no ? ' · Bed ' . $shift->admission->bed->bed_no : '')
                        : null,
                    'start' => $start,
                    'end' => $end,
                ]);
            }
        }

        // Every ADL and Medical service assigned to this employee today, whether the
        // schedule is in the facility or a homecare visit.
        $assignments = ScheduleAssigned::active()
            ->where('employee_id', $employee->employee_id)
            ->whereHas(
                'scheduleService.schedule',
                fn($schedule) => $schedule
                    ->whereBetween('scheduled_at', [$dayStart, $dayEnd])
                    ->whereNotIn('status', ['cancelled', 'missed'])
                    ->whereHas('patient', fn($patient) => $patient->where('branch_id', $branchId))
            )
            ->with(['scheduleService.service', 'scheduleService.schedule.patient'])
            ->orderBy('schedule_assigned_id')
            ->get()
            ->filter(fn($assignment) => $assignment->scheduleService?->schedule)
            ->groupBy(fn($assignment) => $assignment->scheduleService->schedule_id);

        foreach ($assignments as $group) {
            $schedule = $group->first()->scheduleService->schedule;

            // Services in one visit run back to back from the scheduled time.
            $cursor = $schedule->scheduled_at->copy();

            foreach ($group as $assignment) {
                $service = $assignment->scheduleService;

                if ($assignment->start_time && $assignment->end_time) {
                    $start = $schedule->scheduled_at->copy()->setTimeFromTimeString((string) $assignment->start_time);
                    $end = $schedule->scheduled_at->copy()->setTimeFromTimeString((string) $assignment->end_time);

                    if ($end->lessThanOrEqualTo($start)) {
                        $end->addDay();
                    }
                } else {
                    $minutes = $this->serviceMinutes($service);

                    $start = $cursor->copy();
                    $end = $start->copy()->addMinutes($minutes);
                    $cursor = $end->copy();
                }

                if ($end->lessThanOrEqualTo($dayStart) || $start->greaterThan($dayEnd)) {
                    continue;
                }

                $blocks->push([
                    'type' => strtolower((string) $service->type),
                    'setting' => $schedule->category === 'Homecare' ? 'homecare' : 'facility',
                    'title' => $service->service?->service_name ?? $service->type,
                    'patient_name' => $schedule->patient?->display_name,
                    'location' => null,
                    'start' => $start,
                    'end' => $end,
                ]);
            }
        }

        $blocks = $blocks->sortBy(fn($block) => $block['start']->getTimestamp())->values();

        $format = fn(array $block) => [
            'type' => $block['type'],
            'setting' => $block['setting'],
            'title' => $block['title'],
            'patient_name' => $block['patient_name'],
            'location' => $block['location'],
            'start_at' => $block['start']->toISOString(),
            'end_at' => $block['end']->toISOString(),
            'total_minutes' => (int) $block['start']->diffInMinutes($block['end']),
        ];

        $current = $blocks->first(fn($block) => $now->greaterThanOrEqualTo($block['start']) && $now->lessThan($block['end']));
        $next = $blocks->first(fn($block) => $block['start']->greaterThan($now));

        return [
            'on_duty' => (bool) $current,
            'current' => $current ? $format($current) : null,
            'next' => $next ? $format($next) : null,
            'blocks' => $blocks->map($format)->all(),
            'server_now' => $now->toISOString(),
        ];
    }
}