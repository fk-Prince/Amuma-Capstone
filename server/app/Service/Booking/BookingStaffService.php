<?php

namespace App\Service\Booking;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\EmployeeBranch;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Repository\BookingStaffRequestRepository;
use App\Repository\ScheduleRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;

class BookingStaffService
{
    public const PER_PAGE = 5;

    public function __construct(private BookingStaffRequestRepository $requests) {}

    public function options(User $user, Branch $branch, array $payload)
    {
        [$roles, $serviceId] = $this->rolesFor(
            $branch->branch_id,
            $payload['type'] ?? null,
            $payload['service_id'] ?? null
        );

        $page = max(1, (int) ($payload['page'] ?? 1));

        $rows = $this->requests->options(
            $branch->branch_id,
            $roles,
            $serviceId,
            $this->patientFor($user, $branch, $payload['patient_id'] ?? null),
            trim((string) ($payload['search'] ?? '')) ?: null,
            self::PER_PAGE,
            $page
        );

        return response()->json([
            'data' => collect($rows->items())->map(fn($row) => $this->present($row, $serviceId))->values(),
            'meta' => [
                'page' => $rows->currentPage(),
                'total' => $rows->total(),
                'has_more' => $rows->hasMorePages(),
                'role' => count($roles) === 1 ? $roles[0] : 'staff',
            ],
        ]);
    }

    public function resolve(int $branchId, array $homecare, mixed $requested): array
    {
        $values = collect(is_array($requested) ? $requested : [])->unique()->values();

        if ($values->isEmpty()) {
            return [];
        }

        $uuids = $values->filter(fn($uuid) => is_string($uuid) && Str::isUuid($uuid))->values();

        if ($uuids->count() !== $values->count()) {
            throw new Exception('Some of the staff you chose could not be found. Please choose again.', 422);
        }

        $type = $homecare['type'] ?? null;

        $stayIn = $type === Booking::TYPE_ADL
            && ($homecare['billing'] ?? null) === BookingHelper::ADL_BILLING_MONTHLY
            && filter_var($homecare['stay_in'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($stayIn && $uuids->count() > 1) {
            throw new Exception('A stay-in booking can only request one caregiver.', 422);
        }

        [$roles, $serviceId] = $this->rolesFor(
            $branchId,
            $type,
            $type === Booking::TYPE_MEDICAL ? ($homecare['services'][0]['service_id'] ?? null) : null
        );

        $employeeIds = $this->requests->eligibleEmployeeIds($branchId, $roles, $serviceId, $uuids->all());

        if ($employeeIds->count() !== $uuids->count()) {
            throw new Exception(
                'Some of the staff you chose can no longer be requested for this service. Please choose again.',
                422
            );
        }

        return $employeeIds->all();
    }

    public function attach(Booking $booking, array $employeeIds): void
    {
        if (!$employeeIds) {
            return;
        }

        $this->requests->attach($booking->booking_id, $employeeIds);
    }

    public function availabilitySummary(int $branchId, array $homecare, array $requestedStaff): array
    {
        $type = $homecare['type'] ?? null;
        $serviceIds = collect($homecare['services'] ?? [])
            ->pluck('service_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->values();
        $serviceId = $type === Booking::TYPE_MEDICAL
            ? $serviceIds->first()
            : null;

        if ($type === Booking::TYPE_MEDICAL && !$serviceId) {
            return [
                'requested_uuids' => [],
                'other_count' => 0,
                'label' => 'qualified staff',
            ];
        }

        [$roles, $serviceId] = $this->rolesFor($branchId, $type, $serviceId);

        $timezone = ScheduleRepository::branchTimezone((string) $branchId);
        $startValue = trim((string) ($homecare['date'] ?? '') . ' ' . (string) ($homecare['prefered_time'] ?? ''));
        $targetStart = $startValue !== ''
            ? Carbon::parse($startValue, $timezone)
            : null;
        $targetDurationMinutes = $type === Booking::TYPE_MEDICAL
            ? $this->medicalDurationMinutes($serviceIds->all())
            : max(1.0, (float) ($homecare['time_span'] ?? 1)) * 60;
        $targetEnd = $targetStart?->copy()->addMinutes($targetDurationMinutes);

        $staff = $this->requests->availableForService(
            $branchId,
            $roles,
            $serviceId,
            $targetStart,
            $targetEnd,
        );
        $availableUuids = $staff
            ->map(fn($row) => $row->employees?->users?->uuid)
            ->filter()
            ->values();
        $requestedUuids = collect($requestedStaff)
            ->pluck('uuid')
            ->filter(fn($uuid) => $uuid && $availableUuids->contains($uuid))
            ->unique()
            ->values();
        $otherCount = max(0, $availableUuids->unique()->count() - $requestedUuids->count());

        $label = match ($roles) {
            [Service::STAFF_ROLE_CAREGIVER] => 'caregivers',
            [Service::STAFF_ROLE_NURSE] => 'nurses',
            default => 'caregivers or nurses',
        };

        return [
            'requested_uuids' => $requestedUuids->all(),
            'other_count' => $otherCount,
            'label' => $label,
        ];
    }

    private function medicalDurationMinutes(array $serviceIds): float
    {
        if (!$serviceIds) {
            return 60;
        }

        $services = Service::query()
            ->whereIn('service_id', $serviceIds)
            ->pluck('maximum_duration', 'service_id');

        return collect($serviceIds)->sum(function ($serviceId) use ($services) {
            $duration = $services->get($serviceId);

            if (!$duration) {
                return 60;
            }

            [$hours, $minutes, $seconds] = array_pad(explode(':', $duration), 3, 0);

            return ((int) $hours * 60) + (int) $minutes + ((int) $seconds / 60);
        });
    }

    private function rolesFor(int $branchId, ?string $type, mixed $serviceId): array
    {
        if ($type === Booking::TYPE_ADL) {
            return [[Service::STAFF_ROLE_CAREGIVER], null];
        }

        if ($type !== Booking::TYPE_MEDICAL) {
            throw new Exception('Choose a booking type first.', 422);
        }

        $service = $serviceId
            ? Service::where('service_id', $serviceId)->where('branch_id', $branchId)->first()
            : null;

        if (!$service) {
            throw new Exception('Choose a medical service first.', 422);
        }

        return [$service->allowedStaffRoles(), (int) $service->service_id];
    }

    private function patientFor(User $user, Branch $branch, mixed $patientId): ?int
    {
        if (!$patientId) {
            return null;
        }

        $patient = Patient::where('patient_id', $patientId)
            ->where('branch_id', $branch->branch_id)
            ->first();

        if (!$patient) {
            return null;
        }

        $clientId = $user->client?->client_id;

        if ($clientId && $this->requests->hasPatientAccess($clientId, $patient->patient_id)) {
            return $patient->patient_id;
        }

        $employeeId = $user->employee?->employee_id;

        $isStaff = $employeeId && EmployeeBranch::where('branch_id', $branch->branch_id)
            ->where('employee_id', $employeeId)
            ->exists();

        return $isStaff ? $patient->patient_id : null;
    }

    private function present(EmployeeBranch $row, ?int $serviceId): array
    {
        $employee = $row->employees;
        $specialized = $serviceId ? (bool) $row->is_specialized : true;

        return [
            'uuid' => $employee?->users?->uuid,
            'full_name' => $employee?->full_name ?? '',
            'role' => $row->role_name,
            'avatar' => $employee?->avatar,
            'achievement_titles' => collect($employee?->achievements ?? [])
                ->pluck('label')
                ->filter()
                ->values(),
            'is_eligible' => $specialized,
            'reason' => $specialized ? null : 'not_specialized',
            'cared_before' => (bool) ($row->cared_before ?? false),
        ];
    }
}
