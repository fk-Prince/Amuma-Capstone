<?php

namespace App\Service;

use App\Repository\PatientAccessRepository;
use App\Repository\BookingRepository;
use App\Http\Resources\PatientAccessResource;
use App\Models\Booking;
use App\Models\PatientAdmission;
use App\Models\Schedule;
use App\Models\ScheduleService;
use App\Models\User;
use Carbon\Carbon;
use Exception;

class PatientAccessService
{
    public function __construct(
        private PatientAccessRepository $patientAccessRepository,
        private BookingRepository $bookingRepository,
        private NotificationService $notificationService,
        private BookingService $bookingService,
        private PatientAdmissionService $patientAdmissionService,
    ) {}


    public function overview(array $payload)
    {
        return $this->patientAccessRepository->overview($payload);
    }

    public function scheduleList(array $payload)
    {
        return $this->patientAccessRepository->scheduleList($payload);
    }

    public function bookings(array $payload)
    {
        return $this->patientAccessRepository->bookings($payload);
    }


    public function cancelAdmission(array $payload, User $user)
    {
        $access = $this->patientAccessRepository->verifyAccess($payload);
        $patient = $access->patient()->with('currentAdmission')->firstOrFail();

        $admission = $patient->currentAdmission;

        if (!$admission || $admission->status !== PatientAdmission::STATUS_ADMITTED) {
            throw new Exception('This patient is not currently admitted.', 422);
        }

        return $this->patientAdmissionService->cancelAdmission([
            'admission_id' => $admission->patient_admission_id,
            'uuid' => $patient->uuid,
            'note' => $payload['reason'] ?? null,
            'cancelled_by' => 'client',
        ]);
    }

    public function bookAgain(array $payload, User $user)
    {
        $access = $this->patientAccessRepository->verifyAccess($payload);
        $patient = $access->patient()->with(['location', 'branch'])->firstOrFail();
        $guardian = $access->client()->with('user')->first();

        if (!$patient->branch) {
            throw new Exception('This patient has no branch on file.', 422);
        }

        if (!$patient->branch->hasHomecareSubscription()) {
            throw new Exception('This branch no longer support homecare service, so a new homecare service can\'t be requested right now.', 422);
        }

        $bookingData = [
            'patient' => [
                'patient_id'    => $patient->patient_id,
                'first_name'    => $patient->first_name,
                'middle_name'   => $patient->middle_name,
                'last_name'     => $patient->last_name,
                'gender'        => $patient->gender,
                'date_of_birth' => $patient->date_of_birth,
                'phone_number'  => $patient->phone_number,
                'blood_type'    => $patient->blood_type,
                'citizenship'   => $patient->citizenship,
                'address'       => $patient->location?->full_address,
                'allergies'     => $patient->allergies,
            ],
            'guardian' => [
                'first_name'   => $guardian?->first_name,
                'last_name'    => $guardian?->last_name,
                'phone_number' => $guardian?->phone_number,
                'email'        => $guardian?->user?->email,
                'relationship' => $access->relationship_type,
            ],
            'homecare' => [
                'type'          => $payload['type'],
                'date'          => $payload['date'],
                'prefered_time' => $payload['prefered_time'],
                'time_span'     => $payload['time_span'] ?? null,
                'address'       => $payload['address'],
                'latitude'      => $payload['latitude'] ?? null,
                'longitude'     => $payload['longitude'] ?? null,
                'services'      => $payload['services'] ?? [],
                'price'         => $payload['type'] === ScheduleService::TYPE_ADL
                    ? ($payload['rate'] ?? null)
                    : null,
            ],
            'payment' => [
                'total_amount' => $payload['price'] ?? 0,
            ],
        ];

        $validUntil = Carbon::parse($payload['date'] . ' ' . $payload['prefered_time']);

        $booking = $this->bookingRepository->create([
            'user_id'      => $user->user_id,
            'branch_id'    => $patient->branch_id,
            'category'     => Booking::CATEGORY_ONLINE,
            'booking_data' => $bookingData,
            'status'       => Booking::STATUS_PENDING,
            'valid_until'  => $validUntil,
            'booking_type' => Booking::BOOKINGTYPE_ONLINE,
        ]);

        $this->notificationService->notifyNewBooking($patient->branch, $user, $booking);

        return [
            'success' => true,
            'message' => 'Booking request submitted. The branch will review and confirm it shortly.',
            'data' => [
                'booking_id'   => $booking->booking_id,
                'reference_id' => $booking->reference_id,
            ],
        ];
    }

    public function requestScheduleReview(array $payload, User $user)
    {
        $access = $this->patientAccessRepository->verifyAccess($payload);
        $patient = $access->patient()->with('branch')->firstOrFail();

        if (!$patient->branch) {
            throw new Exception('This patient has no branch on file.', 422);
        }

        $schedule = Schedule::where('schedule_id', $payload['schedule_id'])
            ->where('patient_id', $patient->patient_id)
            ->firstOrFail();

        $patientName = trim("{$patient->first_name} {$patient->last_name}");

        $message = ($patientName !== '' ? "{$patientName}'s family" : 'A family member')
            . " requested a review of schedule {$schedule->schedule_code}.";

        $this->notificationService->notifyBranchStaff(
            $patient->branch,
            $message,
            'Schedule',
            $user,
            $schedule,
            (string) $schedule->schedule_id,
            ['admission'],
        );

        return response()->json([
            'message' => 'Admission staff have been notified to review this schedule.',
        ]);
    }

    public function requestInvoiceDeduction(array $payload, User $user)
    {
        $access = $this->patientAccessRepository->verifyAccess($payload);
        $patient = $access->patient()->with('branch')->firstOrFail();

        if (!$patient->branch) {
            throw new Exception('This patient has no branch on file.', 422);
        }

        $schedule = Schedule::where('schedule_id', $payload['schedule_id'])
            ->where('patient_id', $patient->patient_id)
            ->firstOrFail();

        $amount = (float) ($payload['amount'] ?? 0);

        if ($amount <= 0) {
            throw new Exception('Enter a deduction amount greater than zero.', 422);
        }

        $reason = trim((string) ($payload['reason'] ?? ''));
        $patientName = trim("{$patient->first_name} {$patient->last_name}");

        $message = ($patientName !== '' ? "{$patientName}'s family" : 'A family member')
            . ' requested a ₱' . number_format($amount, 2)
            . " deduction for the invoice for schedule {$schedule->schedule_code}"
            . ($reason !== '' ? " due to {$reason}." : '.');

        $this->notificationService->notifyAccountingStaff(
            $patient->branch,
            $message,
            $user,
            $schedule,
            (string) $schedule->schedule_id,
        );

        return response()->json([
            'message' => 'Accounting has been notified to review this deduction request.',
        ]);
    }
}
