<?php

namespace App\Service;

use App\Models\AdditionalCharge;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PatientAdmission;
use App\Models\PatientDiagnosis;
use App\Repository\AdditionalChargeRepository;
use App\Repository\DiagnosisCaseRepository;
use App\Repository\InvoiceRepository;
use App\Service\External\SupabaseService;
use Illuminate\Http\UploadedFile;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdditionalChargeService
{
    public function __construct(
        private AdditionalChargeRepository $additionalChargeRepository,
        private DiagnosisCaseRepository $diagnosisCaseRepository,
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
            'data' => $this->formatMany($charges->getCollection()),
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
            ->whereIn('status', [PatientAdmission::STATUS_ADMITTED, PatientAdmission::STATUS_WAITING])
            ->latest('patient_admission_id')
            ->first();

        if (!$admission) {
            throw new Exception('Charges can only be added while the patient is admitted or waiting to be admitted.', 422);
        }

        $lines = collect($payload['charges']);

        $cases = $this->resolveDiagnosisCases($lines, $payload['branch_id']);
        $diagnoses = $this->resolveDiagnoses($lines, $patient->patient_id);

        $charges = DB::transaction(function () use ($payload, $admission, $lines, $cases, $diagnoses, $patient) {
            $invoice = $this->invoiceRepository->create([
                'total_amount' => round((float) $lines->sum('amount'), 2),
                'branch_id' => $payload['branch_id'],
                'status' => Invoice::STATUS_PENDING,
            ]);

            return $lines->map(function ($line) use ($admission, $invoice, $cases, $diagnoses, $patient) {
                $charge = $this->additionalChargeRepository->create([
                    'patient_admission_id' => $admission->patient_admission_id,
                    'invoice_id' => $invoice->invoice_id,
                    'type' => $line['type'],
                    'description' => trim($line['description']),
                    'amount' => $line['amount'],
                ]);

                $diagnosis = $diagnoses->get($line['patient_diagnosis_uuid'] ?? '')
                    ?? $this->createDiagnosis($patient->patient_id, $line);
                $case = $cases->get($line['diagnosis_case_uuid'] ?? '');

                if ($diagnosis) {
                    $this->additionalChargeRepository->attachDiagnosis(
                        $charge,
                        $diagnosis,
                        $case?->diagnosis_case_id
                    );
                }

                return $charge;
            });
        });

        $charges->each->load(['invoice', 'patientAdmission', 'patientDiagnosis']);

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
            'data' => $this->formatMany($charges),
        ], 201);
    }

    public function diagnoses(array $payload)
    {
        $patient = $this->resolvePatient($payload);

        return response()->json([
            'data' => $this->additionalChargeRepository
                ->listDiagnoses($patient->patient_id)
                ->map(function ($diagnosis) {
                    $charge = $diagnosis->additionalCharge->first();

                    return [
                        'uuid' => $diagnosis->uuid,
                        'diagnosis' => $diagnosis->diagnosis,
                        'diagnosis_date' => $diagnosis->diagnosis_date?->toDateString(),
                        'charged' => (bool) $charge,
                        'paid' => $charge?->invoice?->status === Invoice::STATUS_PAID,
                        'invoice_code' => $charge?->invoice?->invoice_code,
                    ];
                })
                ->values(),
        ]);
    }

    private function createDiagnosis(int $patientId, array $line): ?PatientDiagnosis
    {
        $data = $line['new_diagnosis'] ?? null;

        if ($line['type'] !== AdditionalCharge::TYPE_DIAGNOSIS_CASE || empty($data)) {
            return null;
        }

        $file = $data['diagnosis_file'] ?? null;

        return $this->additionalChargeRepository->createDiagnosis([
            'patient_id' => $patientId,
            'diagnosis' => trim($data['diagnosis']),
            'diagnosis_date' => $data['diagnosis_date'],
            'diagnosis_notes' => filled($data['diagnosis_notes'] ?? null) ? trim($data['diagnosis_notes']) : null,
            'diagnosis_file' => $file instanceof UploadedFile ? $this->uploadDiagnosisFile($file) : null,
        ]);
    }

    private function uploadDiagnosisFile(UploadedFile $file): ?string
    {
        try {
            return SupabaseService::store($file)['url'] ?? null;
        } catch (\Throwable $e) {
            throw new Exception(
                'We couldn\'t upload the diagnosis file. Please try again or use a different file.',
                422,
                $e
            );
        }
    }

    private function resolveDiagnoses($lines, int $patientId)
    {
        $diagnoses = collect();

        foreach ($lines as $index => $line) {
            if ($line['type'] !== AdditionalCharge::TYPE_DIAGNOSIS_CASE) {
                continue;
            }

            $uuid = $line['patient_diagnosis_uuid'] ?? null;

            if (blank($uuid)) {
                if (empty($line['new_diagnosis'])) {
                    throw ValidationException::withMessages([
                        "charges.$index.patient_diagnosis_uuid" => 'Select or add a diagnosis.',
                    ]);
                }

                continue;
            }

            $diagnosis = $diagnoses->has($uuid)
                ? null
                : $this->additionalChargeRepository->findUnbilledDiagnosis($uuid, $patientId);

            if (!$diagnosis) {
                throw ValidationException::withMessages([
                    "charges.$index.patient_diagnosis_uuid" => 'Choose a diagnosis that has not been charged yet.',
                ]);
            }

            $diagnoses->put($uuid, $diagnosis);
        }

        return $diagnoses;
    }

    private function resolveDiagnosisCases($lines, int $branchId)
    {
        $cases = collect();

        foreach ($lines as $index => $line) {
            if ($line['type'] !== AdditionalCharge::TYPE_DIAGNOSIS_CASE || blank($line['diagnosis_case_uuid'] ?? null)) {
                continue;
            }

            $case = $this->diagnosisCaseRepository->findInBranch($line['diagnosis_case_uuid'], $branchId);

            if (!$case) {
                throw ValidationException::withMessages([
                    "charges.$index.diagnosis_case_uuid" => 'Choose a valid diagnosis case.',
                ]);
            }

            $cases->put($case->uuid, $case);
        }

        return $cases;
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

    private function formatMany($charges): array
    {
        $caseIds = $charges
            ->map(fn(AdditionalCharge $charge) => $charge->patientDiagnosis->first()?->pivot?->diagnosis_case_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $cases = $caseIds ? $this->diagnosisCaseRepository->findManyByIds($caseIds) : collect();

        return $charges
            ->map(fn(AdditionalCharge $charge) => $this->format($charge, $cases))
            ->values()
            ->all();
    }

    private function format(AdditionalCharge $charge, $cases): array
    {
        $diagnosis = $charge->patientDiagnosis->first();
        $case = $cases->get($diagnosis?->pivot?->diagnosis_case_id);

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
            'invoice_total' => $charge->invoice ? (float) $charge->invoice->total_amount : null,
            'diagnosis' => $diagnosis?->diagnosis,
            'diagnosis_date' => $diagnosis?->diagnosis_date?->toDateString(),
            'diagnosis_case' => $case?->title,
            'created_at' => $charge->created_at?->toIso8601String(),
        ];
    }
}
