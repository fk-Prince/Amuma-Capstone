<?php

namespace App\Factories;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Repository\InvoiceRepository;
use App\Repository\PaymentRepository;
use App\Service\PatientAdmissionService;
use App\Service\PatientService;
use App\Service\TransactionService;
use App\Utils\AccommodationHelper;
use Exception;
use Illuminate\Support\Facades\Log;

class BookingFactory
{
    public function __construct(
        private PatientService $patientService,
        private InvoiceRepository $invoiceRepository,
        private PatientAdmissionService $patientAdmissionService,
        private PaymentRepository $paymentRepository,
        private TransactionService $transactions
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
        $client = $data['patientAccess'];
        $payload['patient_id'] = $patient['patient_id'];

        $admission = $this->patientAdmissionService->registerPatientBed($payload);

        $invoice = $this->invoiceRepository->create([
            'total_amount' => $payload['payment']['total_amount'],
            'branch_id' => $payload['branch_id'],
            'status' => Invoice::STATUS_PAID,
        ]);


        $invoice->invoiceAdmissionLines()->create([
            'price' => $payload['payment']['total_amount'],
            'patient_admission_id' => $admission['patient_admission_id'],
            'branch_contract_id' => $payload['reserved']['contract_id'],
        ]);

        $amount = (float) ($payload['payment']['total_amount'] ?? 0);

        $transaction = $this->transactions->forPayment(
            $amount,
            $payload['branch_id'],
            $patient['patient_id'],
            'Payment received with the admission booking.',
            [
                'client_id' => $client->client_id,
                'method' => $payload['payment']['payment_method'] ?? 'cash',
                'masked_account_number' => $payload['payment']['masked_card_number'] ?? null,
                'transaction_reference_id' => $payload['payment']['xendit_invoice_id'] ?? null,
            ]
        );

        $receipt = $this->paymentRepository->create([
            'transaction_id' => $transaction->transaction_id,
            'payor_name' => trim(
                ($client->first_name ?? '') . ' ' . ($client->last_name ?? '')
            ) ?: null,
            'prior_balance' => $amount,
            'new_balance' => 0,
            'created_at' => now(),
        ]);

        $receipt->allocations()->create([
            'invoice_id' => $invoice->invoice_id,
            'amount' => $amount,
            'description' => $invoice->paymentDescription(),
            'created_at' => now(),
        ]);

        AccommodationHelper::activate($invoice);

        return [
            'patient'        => $patient,
            'invoice'        => $invoice,
            'admission'      => $admission,
            'patient_access' => $data['patientAccess'],
            'receipt'        => $receipt,
        ];
    }

    public function handleHomecareBooking(array $payload)
    {
        $existingPatientId = $payload['patient']['patient_id'] ?? null;

        if ($existingPatientId) {
            return $this->handleHomecareBookingForExistingPatient($existingPatientId, $payload);
        }

        $data = $this->patientService->createMedicalPatient($payload);
        $invoiceServices = $data['invoiceServices'];

        $invoice = $this->invoiceRepository->create([
            'total_amount' => $payload['payment']['total_amount'],
            'branch_id'    => $payload['branch_id'],
            'status'       => Invoice::STATUS_PENDING,
        ]);

        $invoice->invoiceServices()->createMany($invoiceServices);

        return [
            'invoice' => $invoice,
            'patient' => $data['patient'],
        ];
    }


    private function handleHomecareBookingForExistingPatient(int $patientId, array $payload)
    {
        $patient = Patient::findOrFail($patientId);

        $data = $this->patientService->createHomecareScheduleForExisting(
            $patient,
            $payload['homecare'],
            $payload['category'] ?? 'Homecare',
            $payload['payment']['total_amount'] ?? null,
        );

        $invoice = $this->invoiceRepository->create([
            'total_amount' => $payload['payment']['total_amount'] ?? 0,
            'branch_id'    => $payload['branch_id'] ?? $patient->branch_id,
            'status'       => Invoice::STATUS_PENDING,
        ]);

        $invoice->invoiceServices()->createMany($data['invoiceServices']);

        return [
            'invoice' => $invoice,
            'patient' => $patient,
        ];
    }
}
