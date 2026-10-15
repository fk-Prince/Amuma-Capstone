<?php

namespace App\Service;

use App\Repository\PatientAccessRepository;
use App\Http\Resources\PatientAccessResource;
use App\Models\Booking;
use App\Models\PatientAdmission;
use App\Models\Schedule;
use App\Models\ScheduleService;
use App\Models\User;
use Exception;

class PatientAccessService
{
    public function __construct(
        private PatientAccessRepository $patientAccessRepository,
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

    public function invoices(array $payload)
    {
        return $this->patientAccessRepository->invoicePage($payload);
    }

    public function invoice(array $payload)
    {
        return $this->patientAccessRepository->invoiceDetail($payload);
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

        if (!in_array($payload['payment_method'] ?? null, ['CREDIT-CARD', 'GCASH'], true)) {
            throw new Exception('Choose a payment method to pay the downpayment.', 422);
        }

        $bookingData = [
            'patient' => [
                'patient_id'    => $patient->patient_id,
                'first_name'    => $patient->first_name,
                'middle_name'   => $patient->middle_name,
                'last_name'     => $patient->last_name,
                'suffix'        => $patient->suffix,
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
        ];

        return $this->bookingService->completePayment($user, [
            'category'          => Booking::CATEGORY_ONLINE,
            'branch'            => $patient->branch,
            'branch_id'         => $patient->branch->branch_id,
            'branch_uuid'       => $patient->branch->uuid,
            'booking_data'      => $bookingData,
            'payment_method'    => $payload['payment_method'] ?? null,
            'token_id'          => $payload['token_id'] ?? null,
            'authentication_id' => $payload['authentication_id'] ?? null,
        ]);
    }

    public function payBooking(array $payload, User $user)
    {
        return $this->bookingService->payPortalBooking($user, $payload);
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

        if (!in_array(strtolower((string) $schedule->status), [Schedule::STATUS_PENDING, Schedule::STATUS_ONGOING, Schedule::STATUS_MISSED], true)) {
            throw new Exception('Only pending, ongoing or missed schedules can be sent for review.', 422);
        }

        $patientName = $patient->display_name;

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
}
