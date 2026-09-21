<?php

namespace App\Factories;

use App\Models\AdmissionPeriod;
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

        $admissionPeriod = AdmissionPeriod::where('patient_admission_id', $admission['patient_admission_id'])
            ->latest('admission_period_id')
            ->first();

        $totalAmount = (float) ($payload['payment']['total_amount'] ?? 0);

        $invoice = $this->invoiceRepository->create([
            'total_amount' => $totalAmount,
            'branch_id' => $payload['branch_id'],
            'status' => Invoice::STATUS_PENDING,
        ]);


        $invoice->invoiceAdmissionLines()->create([
            'price' => $totalAmount,
            'admission_period_id' => $admissionPeriod->admission_period_id,
        ]);

        $amount = (float) ($payload['payment']['booking_amount'] ?? $totalAmount);

        $transaction = $this->transactions->forPayment(
            $amount,
            $payload['branch_id'],
            $patient['patient_id'],
            'Payment received with the admission booking.',
            [
                'client_id' => $client->client_id,
                'method' => $payload['payment']['payment_method'] ?? 'cash',
                'party_name' => trim(
                    ($client->first_name ?? '') . ' ' . ($client->last_name ?? '')
                ) ?: null,
                'masked_account_number' => $payload['payment']['masked_card_number'] ?? null,
                'transaction_reference_id' => $payload['payment']['xendit_invoice_id'] ?? null,
            ]
        );

        $receipt = $this->paymentRepository->create([
            'transaction_id' => $transaction->transaction_id,
            'prior_balance' => $totalAmount,
            'new_balance' => round(max($totalAmount - $amount, 0), 2),
            'cash_tendered' => $amount,
            'created_at' => now(),
        ]);

        $receipt->allocations()->create([
            'invoice_id' => $invoice->invoice_id,
            'amount' => $amount,
            'description' => $invoice->paymentDescription(),
            'created_at' => now(),
        ]);

        $invoice->syncStatus();
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
