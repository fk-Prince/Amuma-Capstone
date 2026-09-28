<?php

namespace App\Service;

use App\Models\AdditionalCharge;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PatientAdmission;
use App\Repository\AdditionalChargeRepository;
use App\Repository\InvoiceRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdditionalChargeService
{
    public function __construct(
        private AdditionalChargeRepository $additionalChargeRepository,
        private InvoiceRepository $invoiceRepository,
        private NotificationService $notificationService,
    ) {}

    public function list(array $payload)
    {
        $patient = $this->resolvePatient($payload);

        $charges = $this->additionalChargeRepository->paginateForPatient(
            $patient->patient_id,
            min(50, max(1, (int) ($payload['per_page'] ?? 10)))
        );

        return response()->json([
            'data' => $charges->getCollection()
                ->map(fn(AdditionalCharge $charge) => $this->format($charge))
                ->values(),
            'meta' => [
                'current_page' => $charges->currentPage(),
                'last_page' => $charges->lastPage(),
                'per_page' => $charges->perPage(),
                'total' => $charges->total(),
            ],
        ]);
    }

    public function store(array $payload)
    {
        $patient = $this->resolvePatient($payload);

        $admission = PatientAdmission::where('patient_id', $patient->patient_id)
            ->where('status', PatientAdmission::STATUS_ADMITTED)
            ->latest('patient_admission_id')
            ->first();

        if (!$admission) {
            throw new Exception('Charges can only be added while the patient is admitted.', 422);
        }

        $lines = collect($payload['charges']);

        $charges = DB::transaction(function () use ($payload, $admission, $lines) {
            $invoice = $this->invoiceRepository->create([
                'total_amount' => round((float) $lines->sum('amount'), 2),
                'branch_id' => $payload['branch_id'],
                'status' => Invoice::STATUS_PENDING,
            ]);

            return $lines->map(fn($line) => $this->additionalChargeRepository->create([
                'patient_admission_id' => $admission->patient_admission_id,
                'invoice_id' => $invoice->invoice_id,
                'type' => $line['type'],
                'description' => trim($line['description']),
                'amount' => $line['amount'],
            ]));
        });

        $charges->each->load(['invoice', 'patientAdmission']);

        Invoice::forgetPatientInvoiceIds();

        $invoice = $charges->first()->invoice;

        $this->notificationService->notifyPatientAccess(
            $patient,
            sprintf(
                'A new charge of ₱%s was added to %s %s\'s bill (%s): %s.',
                number_format((float) $invoice->total_amount, 2),
                $patient->first_name,
                $patient->last_name,
                $invoice->invoice_code,
                $invoice->paymentDescription()
            ),
            'Charge Added',
            Auth::user(),
            null,
            $invoice->invoice_code
        );

        return response()->json([
            'message' => $charges->count() === 1 ? 'Charge added and invoiced.' : 'Charges added and invoiced.',
            'data' => $charges
                ->map(fn(AdditionalCharge $charge) => $this->format($charge))
                ->values(),
        ], 201);
    }

    private function resolvePatient(array $payload): Patient
    {
        $patient = Patient::where('uuid', $payload['patient_uuid'])
            ->where('branch_id', $payload['branch_id'])
            ->first();

        if (!$patient) {
            throw new Exception('Patient not found.', 404);
        }

        return $patient;
    }

    private function format(AdditionalCharge $charge): array
    {
        return [
            'additional_charge_id' => $charge->additional_charge_id,
            'patient_admission_id' => $charge->patient_admission_id,
            'admitted_at' => $charge->patientAdmission?->admitted_at?->toIso8601String(),
            'admission_status' => $charge->patientAdmission?->status,
            'type' => $charge->type,
            'type_label' => $charge->type_label,
            'description' => $charge->description,
            'amount' => (float) $charge->amount,
            'invoice_code' => $charge->invoice?->invoice_code,
            'invoice_status' => $charge->invoice?->status,
            'created_at' => $charge->created_at?->toIso8601String(),
        ];
    }
}
