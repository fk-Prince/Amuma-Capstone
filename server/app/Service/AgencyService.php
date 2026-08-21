<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Repository\AgencyRepository;
use App\Http\Resources\AgencyResource;
use App\Models\User;
use App\Repository\BranchRepository;
use App\Service\External\SupabaseService;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgencyService
{

    public function __construct(private AgencyRepository $agencyRepository, private BranchRepository $branchRepository) {}

    public function listAgency(array $payload)
    {
        if ($payload['type'] === 'stats') {
            return $this->agencyRepository->stats($payload['agency_id']);
        }

        if ($payload['type'] === 'agency_branches') {
            return $this->agencyRepository->paginate($payload);
        }
    }

    public function update(array $payload)
    {

        $agency = $this->agencyRepository->findAgencyByField('agency_id', $payload['agency_id']);

        if (!$agency) {
            throw new Exception(__('Agency does not exist'), 404);
        }

        return DB::transaction(function () use ($agency, $payload) {

            Log::info($payload);
            if (
                isset($payload['agency_image']) &&
                $payload['agency_image'] instanceof UploadedFile
            ) {
                $image = SupabaseService::store($payload['agency_image']);
            }

            $agency->update([
                'name' => $payload['agency_name'],
                'description' => $payload['agency_description'],
                'email' => $payload['agency_email'] ?? null,
                'image' => $image['url'] ?? $payload['agency_image'] ?? null,
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
