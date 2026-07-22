<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Repository\BranchContractRepository;
use App\Http\Resources\BranchContractResource;
use App\Models\User;
use App\Repository\BranchRepository;
use App\Service\Utils\AuthGuard;
use Exception;

class BranchContractService
{
    private BranchContractRepository $branchContractRepository;
    private BranchRepository $branchRepository;

    public function __construct(BranchContractRepository $branchContractRepository, BranchRepository $branchRepository)
    {
        $this->branchContractRepository = $branchContractRepository;
        $this->branchRepository = $branchRepository;
    }


    public function overview(User $user, array $payload)
    {
        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

        if (!$branch)  throw new Exception("Branch doesn't exist", 404);

        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Pricing,  PermissionAction::Read);

        return [
            'total_active_plans' => $this->branchContractRepository->overview($payload, $branch->branch_id),
            'patient_with_plan' => 5,
            'new_monthy_patients' => 10,
            'patient_retention' => '99%',
            "active_patient" =>  "0",
            "caregivers" =>  "0",
            "scheduled_visits" =>  "0",
            "homecare_retention" =>  "0",
        ];
    }


    public function createBranchContract(User $user, array $payload)
    {

        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

        if (!$branch)  throw new Exception("Branch doesn't exist", 404);

        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Pricing,  PermissionAction::Create);

        $existingContract = $this->branchContractRepository->findByField([
            ['branch_id', '=', $branch->branch_id],
            ['category', '=', $payload['category']],
            ['type', '=', $payload['type']],
            ['billing_interval', '=', $payload['billing_interval']],
        ]);

        if ($existingContract) {
            throw new Exception(
                "A {$payload['category']} {$payload['type']} {$payload['billing_interval']} contract already exists for this branch.",
                409
            );
        }

        $payload = [
            'branch_id' => $branch->branch_id,
            'category' => $payload['category'],
            'type' => $payload['type'],
            'price' => $payload['price'],
            'billing_interval' => $payload['billing_interval'],
            'description' => $payload['description'] ?? null,
        ];

        $contract = $this->branchContractRepository->create($payload);

        return [
            'message' => 'Branch contract created successfully.',
            'data' => $contract,
        ];
    }

    public function list(User $user, array $payload)
    {
        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

        if (!$branch)  throw new Exception("Branch doesn't exist", 404);

        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Pricing,  PermissionAction::Read);

        $data = $this->branchContractRepository->all($branch->branch_id);

        return $data;
    }

    public function updateBranchContract(User $user, array $payload, string $id)
    {
        $branch = $this->branchRepository->findByField('uuid', $payload['branch_uuid']);

        if (!$branch) {
            throw new Exception("Branch doesn't exist", 404);
        }

        AuthGuard::requireModule(
            $user,
            $branch->branch_id,
            ModuleEnum::Pricing,
            PermissionAction::Update
        );

        $contract = $this->branchContractRepository->findByField([
            ['branch_contract_id', '=', $id],
        ]);

        if (!$contract) {
            throw new Exception("Branch contract doesn't exist", 404);
        }

        $existingContract = $this->branchContractRepository->findByField([
            ['branch_id', '=', $branch->branch_id],
            ['category', '=', $payload['category']],
            ['type', '=', $payload['type']],
            ['billing_interval', '=', $payload['billing_interval']],
            ['branch_contract_id', '!=', $payload['branch_contract_id']],
        ]);


        if ($existingContract) {
            throw new Exception(
                "A {$payload['category']} {$payload['type']} {$payload['billing_interval']} contract already exists for this branch.",
                409
            );
        }
        $contract->update([
            'category' => $payload['category'],
            'type' => $payload['type'],
            'price' => $payload['price'],
            'billing_interval' => $payload['billing_interval'],
            'description' => $payload['description'] ?? null,
        ]);

        return [
            'message' => 'Branch contract updated successfully.',
            'data' => new BranchContractResource($contract->fresh()),
        ];
    }
}
