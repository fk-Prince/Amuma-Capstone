<?php

namespace App\Service;

use App\Models\ScheduleService as ScheduleServiceModel;
use App\Repository\PatientAdmissionRepository;
use App\Repository\ScheduleRepository;
use App\Repository\ServiceRepository;

class DashboardAttentionService
{
    private const WINDOW_DAYS = 3;

    private const SERVICE_TYPE_LABELS = [
        'online' => 'Homecare Services',
        'facility' => 'Inhouse Services',
        'both' => 'Homecare and Inhouse Services',
    ];

    public function __construct(
        private PatientAdmissionRepository $patientAdmissionRepository,
        private ServiceRepository $serviceRepository,
        private ScheduleRepository $scheduleRepository,
    ) {}

    public function attention(array $payload)
    {
        $branchId = (int) $payload['branch_id'];
        $until = now()->addDays(self::WINDOW_DAYS)->endOfDay();

        $discharges = $this->patientAdmissionRepository->dischargingBy($branchId, $until)
            ->map(fn($admission) => [
                'patient_uuid' => $admission->patient?->uuid,
                'patient_name' => $admission->patient?->display_name,
                'room_no' => $admission->bed?->room?->room_no,
                'bed_no' => $admission->bed?->bed_no,
                'discharge_at' => $admission->discharged_at,
            ])->values();

        $services = $this->serviceRepository->withoutStaff($branchId)
            ->map(fn($service) => [
                'service_uuid' => $service->service_uuid,
                'service_name' => $service->service_name,
                'category_name' => $service->categories?->category_name,
                'performed_by' => $service->performed_by,
                'type_formatted' => self::SERVICE_TYPE_LABELS[$service->type] ?? null,
            ])->values();

        $schedules = $this->scheduleRepository->unassignedUntil($branchId, $until)
            ->map(fn($schedule) => [
                'schedule_code' => $schedule->schedule_code,
                'patient_name' => $schedule->patient?->display_name,
                'scheduled_at' => $schedule->scheduled_at,
                'type' => $schedule->scheduleServices->contains(
                    fn($service) => $service->type === ScheduleServiceModel::TYPE_ADL
                ) ? 'adl' : 'medical',
                'services' => $schedule->scheduleServices
                    ->map(fn($service) => $service->service?->service_name ?? 'Activities of Daily Living (ADL)')
                    ->values(),
            ])->values();

        return response()->json([
            'data' => [
                'discharges' => $discharges,
                'services' => $services,
                'schedules' => $schedules,
            ],
        ]);
    }
}
