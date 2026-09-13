<?php

namespace App\Hooks;

use App\Service\PatientAdmissionService;

class AdmissionExtensionWebhook
{
    public function __construct(private PatientAdmissionService $patientAdmissionService) {}

    public function handle(array $payload)
    {
        $metadata = $payload['metadata'];

        return $this->patientAdmissionService->extendAdmission([
            'admission_id' => $metadata['admission_id'],
            'contract_id' => $metadata['contract_id'],
            'branch_id' => $metadata['branch_id'],
            'p_uuid' => $metadata['patient_uuid'] ?? null,
            'require_payment' => true,
            'cash' => $metadata['amount'],
            'payment_method' => 'GCASH',
            'payor_name' => $metadata['payor_name'] ?? null,
            'include_patient' => false,
        ]);
    }
}
