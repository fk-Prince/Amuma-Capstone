<?php

namespace App\Service\Booking;

use App\Models\Booking;
use App\Repository\InvoiceRepository;
use App\Service\PatientAdmissionService;
use App\Service\PatientService;
use Exception;

class FacilityBookingService
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private PatientService $patientService,
        private PatientAdmissionService $patientAdmissionService
    ) {}


    public function completeAdmission(array $payload)
    {
        $nonProcessableStatuses = [
            Booking::STATUS_PENDING,
            Booking::STATUS_REJECTED,
            Booking::STATUS_APPROVED,
            Booking::STATUS_EXPIRED,
            Booking::STATUS_COMPLETED
        ];

        if (in_array($payload['status'], $nonProcessableStatuses)) {
            throw new Exception(
                "Booking cannot be processed because its current status is '{$payload['status']}', can only process awaiting status.",
                400
            );
        }
        $invoicePayload = [
            'total' =>  $payload['payment']['total_amount'],
            'branch_id' => $payload['branch_id'],
            'status' => 'paid'
        ];

        $invoice = $this->invoiceRepository->create($invoicePayload);

        $patientData = $this->patientService->createFacilityPatient(
            $payload['branch_id'],
            $payload
        );

        $patient = $patientData['patient'];
        $reserved = $payload['reserved'];
        $admissionPayload = [
            'patient_id' => $patient['patient_id'],
            'bed_id' => $reserved['bed']['bed_id'],
            'admitted_at' => $reserved['admitted_at'],
            'branch_id' => $payload['branch_id'],
            'billing_cycle' => $reserved['billing_cycle'],
        ];
        $admission = $this->patientAdmissionService->registerPatientBed($admissionPayload);

        $facilityInvoicePayload = [
            'branch_contract_id' => $reserved['contract_id'],
            'patient_admission_id' => $admission['patient_admission_id'],
            'price' => $payload['payment']['total_amount'],
        ];
        $paymentPayload = [
            'amount' => $payload['payment']['total_amount'],
            'payment_method' => 'cash'
        ];
        $invoice->invoiceFacility()->create($facilityInvoicePayload);
        $invoice->payments()->create($paymentPayload);

        return [
            $invoice,
            $patientData,
            $admission,
        ];
    }

    public function preAdmisson(array $payload) {}

    public function walkinAdmission(array $payload) {}
}
