<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Repository\AgencyRepository;
use App\Http\Resources\AgencyResource;
use App\Models\User;
use App\Repository\BranchRepository;
use App\Service\Utils\AuthGuard;
use Exception;
use Illuminate\Support\Facades\DB;

class AgencyService
{
    private AgencyRepository $agencyRepository;
    private BranchRepository $branchRepository;

    public function __construct(AgencyRepository $agencyRepository, BranchRepository $branchRepository)
    {
        $this->agencyRepository = $agencyRepository;
        $this->branchRepository = $branchRepository;
    }

    public function listAgency(array $payload)
    {
        $collection = $this->agencyRepository->paginate($payload['per_page'], $payload['owned']);
        return AgencyResource::collection($collection);
    }

    public function update(array $payload, User $user, string $uuid)
    {
        $branch = $this->branchRepository->findByField('uuid', $uuid);

        if (!$branch) {
            throw new Exception(__('Branch does not exist'), 404);
        }

        $agency = $this->agencyRepository->findAgencyByField('agency_id', $branch->agency_id);

        if (!$agency) {
            throw new Exception(__('Agency does not exist'), 404);
        }

        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::BranchSettings, PermissionAction::Update);

        return DB::transaction(function () use ($agency, $payload) {

            $agency->update([
                'name' => $payload['agency_name'],
                'description' => $payload['agency_description'],
            ]);


            $agency->locations()->updateOrCreate(
                [],
                [
                    'street' => $payload['location']['street'],
                    'city' => $payload['location']['city'],
                    'province' => $payload['location']['province'],
                    'country' => $payload['location']['country'],
                    'longitude' => $payload['location']['longitude'] ?? null,
                    'latitude' => $payload['location']['latitude'] ?? null,
                ]
            );

            return response()->json([
                'message' => 'Agency Information Successfully Updated.'
            ], 200);
        });
    }
}
