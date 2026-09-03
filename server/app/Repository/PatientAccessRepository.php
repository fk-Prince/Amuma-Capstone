<?php

namespace App\Repository;

use App\Models\Booking;
use App\Models\PatientAccess;
use App\Models\Schedule;
use App\Models\ScheduleService;
use App\Utils\PortalHelper;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PatientAccessRepository
{
    private const SECTIONS = ['all', 'profile', 'financials', 'schedule', 'medication', 'activity'];

    public function __construct(
        private readonly PortalHelper $helper
    ) {}

    public function overview(array $payload)
    {
        $sections = $this->resolveSections($payload);

        if (!isset($payload['patient_id'])) {
            return $this->getAllPatients($payload, $sections);
        }

        $access = $this->findAccess($payload);

        $patient = $access->patient()
            ->with($this->patientRelations($sections, (bool) ($payload['latest_only'] ?? false)))
            ->firstOrFail();

        return [
            'success' => true,
            'data' => $this->helper->patientPayload($access, $patient, false, $sections),
        ];
    }

    private function getAllPatients(array $payload, array $sections)
    {
        $latestOnly = (bool) ($payload['latest_only'] ?? false);

        $patients = PatientAccess::query()
            ->where('client_id', $payload['client_id'])
            ->where('have_access', true)
            ->whereHas('patient')
            ->with([
                'client.user',
                'patient' => fn($query) =>
                $query->with($this->patientRelations($sections, $latestOnly)),
            ])
            ->get();

        $data = $patients->map(
            fn($access) =>
            $this->helper->patientPayload(
                $access,
                $access->patient,
                true,
                $sections
            )
        );

        return [
            'total' => $data->count(),
            'data' => $data->values(),
        ];
    }


    public function scheduleList(array $payload)
    {
        $access = $this->findAccess($payload);
        $patient = $access->patient()->with('location')->firstOrFail();

        $type = $payload['type'] ?? 'adl';

        $schedules = Schedule::query()
            ->where('patient_id', $patient->patient_id)
            ->when(
                $type === 'adl',
                fn($query) => $query->whereHas(
                    'scheduleServices',
                    fn($serviceQuery) =>
                    $serviceQuery->where('type', ScheduleService::TYPE_ADL)
                )
            )
            ->when(
                $type === 'medical',
                fn($query) => $query->whereHas(
                    'scheduleServices',
                    fn($serviceQuery) =>
                    $serviceQuery
                        ->whereNotNull('service_id')
                        ->where(
                            fn($q) => $q
                                ->whereNull('type')
                                ->orWhere('type', '!=', ScheduleService::TYPE_ADL)
                        )
                )
            )
            ->when(
                !empty($payload['month']) && $payload['month'] !== 'all',
                fn($query) => $query->whereRaw(
                    "to_char(scheduled_at, 'YYYY-MM') = ?",
                    [$payload['month']]
                )
            )
            ->when(
                !empty($payload['status']) && $payload['status'] !== 'all',
                fn($query) => $query->whereIn(
                    'status',
                    match ($payload['status']) {
                        'upcoming' => [Schedule::STATUS_PENDING, 'confirmed'],
                        'missed' => [Schedule::STATUS_MISSED, Schedule::STATUS_CANCELLED],
                        default => [$payload['status']],
                    }
                )
            )
            ->with([
                'scheduleServices.service',
                'scheduleServices.assigned' => fn($assignedQuery) =>
                $assignedQuery->with([
                    'employee.employees.employeeBranch',
                    'onlineSchedules',
                ]),
            ])
            ->orderByDesc('scheduled_at')
            ->paginate((int) ($payload['per_page'] ?? 10));

        return [
            'success' => true,
            'data' => collect($schedules->items())
                ->map(fn($schedule) => $this->helper->schedulePayload($schedule, $patient))
                ->values(),
            'meta' => [
                'current_page' => $schedules->currentPage(),
                'last_page' => $schedules->lastPage(),
                'total' => $schedules->total(),
                'per_page' => $schedules->perPage(),
            ],
        ];
    }


    public function bookings(array $payload)
    {
        if (empty($payload['user_id'])) {
            return [
                'success' => true,
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                    'per_page' => (int) ($payload['per_page'] ?? 10),
                ],
            ];
        }

        $bookings = Booking::where('user_id', $payload['user_id'])
            ->with('branch')
            ->orderByDesc('booking_id')
            ->paginate((int) ($payload['per_page'] ?? 10));

        return [
            'success' => true,
            'data' => collect($bookings->items())
                ->map(fn($booking) => $this->helper->bookingPayload($booking))
                ->values(),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'total' => $bookings->total(),
                'per_page' => $bookings->perPage(),
            ],
        ];
    }

    public function verifyAccess(array $payload)
    {
        return $this->findAccess($payload);
    }

    private function findAccess(array $payload)
    {
        $access = PatientAccess::query()
            ->where('patient_id', $payload['patient_id'])
            ->where('client_id', $payload['client_id'])
            ->firstOrFail();

        if (!$access->have_access) {
            throw new ModelNotFoundException(
                'Access denied to this patient'
            );
        }
        return $access;
    }


    private function resolveSections(array $payload): array
    {
        $requested = $payload['section'] ?? 'all';

        $sections = array_filter(array_map(
            'trim',
            is_array($requested) ? $requested : explode(',', (string) $requested)
        ));

        $sections = array_values(array_intersect(self::SECTIONS, $sections));

        return $sections ?: ['all'];
    }


    private function patientRelations(array $sections, bool $latestOnly = false): array
    {
        $wantsAll = in_array('all', $sections, true);
        $relations = [];


        $orderSchedules = fn($query) => $query
            ->orderByRaw(
                'CASE WHEN status = ? THEN 0 WHEN status = ? THEN 1 ELSE 2 END',
                [Schedule::STATUS_ONGOING, Schedule::STATUS_PENDING]
            )
            ->orderByDesc('scheduled_at');


        if ($wantsAll || in_array('medication', $sections, true)) {
            if ($latestOnly) {
                $relations['medications'] = fn($query) =>
                $query->orderByDesc('recorded_at')
                    ->limit(1)
                    ->with('schedules');

                $relations['vitals'] = fn($query) =>
                $query->orderByDesc('recorded_date')
                    ->orderByDesc('recorded_time')
                    ->limit(1);
            } else {
                $relations = array_merge($relations, [
                    'medications.schedules',
                    'vitals',
                ]);
            }
        }


        if ($wantsAll || in_array('activity', $sections, true)) {
            $relations['activities'] = fn($query) =>
            $query->orderByDesc('occurred_at')->limit(200);
        }

        if ($wantsAll || in_array('profile', $sections, true)) {
            $relations = array_merge($relations, [
                'branch.location',
                'location',
                'currentAdmission.bed.room',
                'latestAdmission.bed.room',
            ]);

            $relations['assessments'] = fn($query) =>
            $query->orderByDesc('created_at')
                ->orderByDesc('patient_assessment_id');

            $relations['diagnoses'] = fn($query) =>
            $query->orderByDesc('diagnosis_date')
                ->orderByDesc('patient_diagnosis_id');


            $relations['schedules'] = fn($query) =>
            $orderSchedules($query->with(['scheduleServices']));
        }

        if ($wantsAll || in_array('schedule', $sections, true)) {
            if (!in_array('location', $relations, true)) {
                $relations[] = 'location';
            }

            $relations['schedules'] = fn($query) =>
            $orderSchedules($query->with([
                'scheduleServices.service',
                'scheduleServices.assigned' => fn($assignedQuery) =>
                $assignedQuery->with([
                    'employee.employees.employeeBranch',
                    'onlineSchedules',
                ]),
            ]));
        }

        return $relations;
    }
}
