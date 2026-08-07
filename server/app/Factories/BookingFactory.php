<?php

namespace App\Factories;

use App\Models\Booking;
use App\Models\Invoice;
use App\Repository\InvoiceRepository;
use App\Service\PatientAdmissionService;
use App\Service\PatientService;
use Exception;

class BookingFactory
{
    public function __construct(
        private PatientService $patientService,
        private InvoiceRepository $invoiceRepository,
        private PatientAdmissionService $patientAdmissionService
    ) {}

    public function process(array $payload)
    {
        if (isset($payload['homecare'])) {
            return $this->handleHomecareBooking($payload);
        }

        if (isset($payload['facility'])) {
            $facility = $payload['facility'];

            return match ($facility['type']) {
                Booking::TYPE_COMPLETEADMISSION => $this->handleCompleteAdmission($payload),
                // Booking::TYPE_PREADMISSION => $this->handleCompleteAdmission($payload),
            };
        }
    }

    public function handleCompleteAdmission(array $payload)
    {
        $data = $this->patientService->createFacilityPatient($payload);
        $patient = $data['patient'];
        $payload['patient_id'] = $patient['patient_id'];

        $admission = $this->patientAdmissionService->registerPatientBed($payload);

        $invoice = $this->invoiceRepository->create([
            'total' => $payload['payment']['total_amount'],
            'branch_id' => $payload['branch_id'],
            'status' => Invoice::STATUS_PAID,
        ]);


        $invoice->invoiceFacility()->create([
            'price' =>  $payload['payment']['total_amount'],
            'patient_admission_id' => $admission['patient_admission_id'],
            'branch_contract_id' => $payload['reserved']['contract_id']
        ]);

        return [
            'patient'        => $patient,
            'invoice'        => $invoice,
            'admission'      => $admission,
            'patient_access' => $data['patientAccess']
        ];
    }

    public function handleHomecareBooking(array $payload)
    {
        $data = $this->patientService->createMedicalPatient($payload);
        $invoiceServices = $data['invoiceServices'];

        $invoice = $this->invoiceRepository->create([
            'total'     => $payload['payment']['total_amount'],
            'branch_id' => $payload['branch_id'],
            'status'    => Invoice::STATUS_PENDING,
        ]);

        $invoice->invoiceServices()->createMany($invoiceServices);

        return [
            'data' => $data,
        ];
    }




    // public function approveCompleteAdmission(Booking $booking, array $payload, array $payload)
    // {
    //     $reserved = $payload['reserved'] ?? [];

    //     if (empty($reserved)) {
    //         throw new Exception('Please select accommodation type.');
    //     }

    //     if (empty($reserved['room']) || empty($reserved['bed'])) {
    //         throw new Exception('Please select a room and bed.');
    //     }

    //     $data = $this->registerPatient($payload);


    //     $booking->update([
    //         'booking_data' => $payload['booking_data'],
    //         'status' => Booking::STATUS_APPROVED
    //     ]);

    //     return [
    //         'data' => [$booking, $data],
    //         'message' => 'Facility booking has been approved.',
    //     ];
    // }

    // public function handlePreAdmission(Booking $booking)
    // {
    //     $booking->update(['status' => Booking::STATUS_APPROVED]);

    //     return [
    //         'data' => $booking,
    //         'message' => 'Facility booking has been approved.',
    //     ];
    // }

    // // TODO:
    // public function handleWalkInAdmission(Booking $booking, array $payload)
    // {
    //     $reserved = $payload['booking']['booking_data']['reserved'] ?? [];
    //     if (empty($reserved)) {
    //         throw new Exception('Please select accommodation type.');
    //     }
    //     if (empty($reserved['room']) || empty($reserved['bed'])) {
    //         throw new Exception('Please select a room and bed.');
    //     }
    //     $data = $this->registerPatient($payload);

    //     $booking->update([
    //         'booking_data' => $booking->booking_data,
    //         'status' => Booking::STATUS_APPROVED,
    //     ]);

    //     return [
    //         'data' => [
    //             $data,
    //             $booking
    //         ],
    //         'message' => 'Facility booking has been approved.',
    //     ];
    // }


    // private function admitPatient(array $payload)
    // {
    //     Log::info($payload);
    //     throw new Exception("xd");
    //     // $data = $this->registerPatient($payload);

    //     // $patient = $data['patient'];

    //     // $patient->bookings()->create([
    //     //     'booking_id' => $booking->booking_id,
    //     //     'invoice_id' => $data['invoice']['invoice_id'],
    //     // ]);

    //     // // $booking->update(['status' => Booking::STATUS_INPROGRESS]);

    //     // return [
    //     //     'data' => $data,
    //     //     'message' => $message,
    //     // ];
    // }

    // public function registerPatient(array $payload)
    // {
    //     $nonProcessableStatuses = [
    //         Booking::STATUS_APPROVED,
    //         Booking::STATUS_REJECTED,
    //         Booking::STATUS_EXPIRED,
    //     ];

    //     if (in_array($payload['status'], $nonProcessableStatuses)) {
    //         throw new Exception(
    //             "Booking cannot be processed because its current status is '{$payload['status']}', can only process awaiting status.",
    //             400
    //         );
    //     }
    //     $invoicePayload = [
    //         'total' =>  $payload['payment']['total_amount'],
    //         'branch_id' => $payload['branch_id'],
    //         'status' => Invoice::STATUS_PENDING
    //     ];

    //     $invoice = $this->invoiceRepository->create($invoicePayload);
    //     $patientData = $this->patientService->createFacilityPatient($payload['branch_id'], $payload);
    //     $patient = $patientData['patient'];
    //     $reserved = $payload['reserved'];

    //     $admissionPayload = [
    //         'booking_id' => $payload['booking_id'] ?? null,
    //         'patient_id' => $patient['patient_id'],
    //         'bed_id' => $reserved['bed']['bed_id'],
    //         'admitted_at' => $reserved['admitted_at'],
    //         'branch_id' => $payload['branch_id'],
    //         'billing_cycle' => $reserved['billing_cycle'],
    //     ];

    //     $admission = $this->patientAdmissionService->registerPatientBed($admissionPayload);

    //     $facilityInvoicePayload = [
    //         'branch_contract_id' => $reserved['contract_id'],
    //         'patient_admission_id' => $admission['patient_admission_id'],
    //         'price' => $payload['payment']['total_amount'],
    //     ];

    //     $paymentPayload = [
    //         'amount' => $payload['payment']['total_amount'],
    //         'payment_method' => 'cash'
    //     ];

    //     $invoice->invoiceFacility()->create($facilityInvoicePayload);

    //     $invoice->payments()->create($paymentPayload);



    //     return [
    //         'patient' => $patient,
    //         'invoice' => $invoice,
    //         $patientData,
    //         $admission,
    //     ];
    // }
}
