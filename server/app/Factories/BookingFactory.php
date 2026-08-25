<?php

namespace App\Factories;

use App\Models\Booking;
use App\Models\Invoice;
use App\Repository\InvoiceRepository;
use App\Service\PatientAdmissionService;
use App\Service\PatientService;
use Exception;
use Illuminate\Support\Facades\Log;

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
            // 'original_total' => $payload['payment']['total_amount'],
            'branch_id' => $payload['branch_id'],
            'status' => Invoice::STATUS_PAID,
        ]);


        $invoice->invoiceFacility()->create([
            'price' => $payload['payment']['total_amount'],
            'patient_admission_id' => $admission['patient_admission_id'],
            'branch_contract_id' => $payload['reserved']['contract_id']
        ]);


        $invoice->payments()->create([
            'amount' => $payload['payment']['total_amount'] ?? null,
            'payment_method' => $payload['payment']['payment_method'] ?? 'cash',
            'reference_id' => $payload['payment']['xendit_invoice_id'] ?? null,
            'masked_card_number' => $payload['payment']['masked_card_number'] ?? null,
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
            // 'original_total'     => $payload['payment']['total_amount'],
            'branch_id' => $payload['branch_id'],
            'status'    => Invoice::STATUS_PENDING,
        ]);

        $invoice->invoiceServices()->createMany($invoiceServices);

        return [
            'invoice' => $invoice,
            'patient' => $data['patient'],
        ];
    }
}
