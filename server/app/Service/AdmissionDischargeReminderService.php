<?php

namespace App\Service;

use App\Repository\PatientAdmissionRepository;
use Illuminate\Support\Carbon;

class AdmissionDischargeReminderService
{
    public const MESSAGE_TYPE = 'Discharge Reminder';
    public const DAYS_BEFORE = 3;

    public function __construct(
        private PatientAdmissionRepository $patientAdmissionRepository,
        private NotificationService $notificationService,
    ) {}

    public function sendReminders(int $daysBefore = self::DAYS_BEFORE): int
    {
        $endDate = Carbon::today()->addDays($daysBefore);
        $sent = 0;

        foreach ($this->patientAdmissionRepository->findEndingOn($endDate) as $admission) {
            $patient = $admission->patient;

            if (!$patient) {
                continue;
            }

            $unit = $daysBefore === 1 ? 'day' : 'days';

            $this->notificationService->notifyPatientAccess(
                $patient,
                sprintf(
                    "%s's stay at %s ends in %d %s (%s). Settle any balance or extend the stay before then, otherwise %s will be discharged.",
                    $patient->display_name,
                    $patient->branch?->name ?? 'the facility',
                    $daysBefore,
                    $unit,
                    $endDate->toFormattedDateString(),
                    $patient->first_name
                ),
                self::MESSAGE_TYPE
            );

            $sent++;
        }

        return $sent;
    }
}
