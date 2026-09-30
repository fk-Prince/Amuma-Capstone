<?php

namespace App\Service;

use App\Http\Resources\DepositResource;
use App\Models\Client;
use App\Models\Patient;
use App\Models\PatientAccess;
use App\Repository\RefundRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class DepositService
{
    public function __construct(
        private TransactionService $transactions,
        private RefundRepository $refundRepository,
        private PaymentService $payments
    ) {}

    public function depositFromPortal(Client $client, array $payload): array
    {
        $access = PatientAccess::where('patient_id', $payload['patient_id'])
            ->where('client_id', $client->client_id)
            ->where('have_access', true)
            ->first();

        if (!$access) {
            throw new Exception('You do not have access to this patient.', 403);
        }

        $patient = $access->patient;

        if (!$patient?->branch_id) {
            throw new Exception('This resident is not linked to a branch yet.', 422);
        }

        $amount = round((float) $payload['amount'], 2);

        $charge = $this->payments->chargeCard($client, $amount, $payload, 'deposit');

        return DB::transaction(fn() => $this->record(
            $patient,
            $amount,
            $patient->branch_id,
            'Deposit made through the family portal.',
            [
                'client_id' => $client->client_id,
                'method' => 'CREDIT-CARD',
                'party_name' => trim(
                    ($client->first_name ?? '') . ' ' . ($client->last_name ?? '')
                ) ?: null,
                'masked_account_number' => $charge['masked_card_number'] ?? null,
                'transaction_reference_id' => $charge['id'] ?? null,
            ]
        ));
    }

    public function depositFromDashboard(Patient $patient, mixed $branchId, array $payload): array
    {
        $amount = round((float) $payload['amount'], 2);
        $note = trim((string) ($payload['note'] ?? ''));

        return DB::transaction(fn() => $this->record(
            $patient,
            $amount,
            $branchId,
            $note !== '' ? $note : 'Deposit received at the branch.',
            [
                'method' => 'CASH',
                'party_name' => trim((string) ($payload['deposited_by'] ?? '')) ?: null,
            ]
        ));
    }

    private function record(
        Patient $patient,
        float $amount,
        mixed $branchId,
        string $description,
        array $extra
    ): array {
        $deposit = $this->transactions->forDeposit(
            $amount,
            $branchId,
            $patient->patient_id,
            $description,
            $extra
        );

        return [
            'success' => true,
            'message' => 'Deposit of ' . number_format($amount, 2) . ' added to the credit on the account.',
            'deposit' => DepositResource::format($deposit->refresh()),
            'available_credit' => $this->refundRepository->creditFor($patient->patient_id),
        ];
    }
}
