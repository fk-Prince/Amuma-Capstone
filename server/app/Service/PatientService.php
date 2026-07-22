<?php

namespace App\Service;

use App\Repository\PatientRepository;
use App\Http\Resources\PatientResource;
use App\Models\User;

class PatientService
{
    private PatientRepository $patientRepository;

    public function __construct(PatientRepository $patientRepository) 
    {
        $this->patientRepository = $patientRepository;
    }

    public function createPatient(User $actor, array $payload)
    {
        if (! $actor->hasRole('superadmin')) {
            $payload['company_id'] = $actor->company_id;
        }

        $model = $this->patientRepository->create($payload);
        return new PatientResource($model);
    }

    public function listPatient(User $actor, int $perPage = 15)
    {
        $companyId = $actor->hasRole('superadmin') ? null : $actor->company_id;

        $collection = $this->patientRepository->paginate($perPage, $companyId);
        return PatientResource::collection($collection);
    }

    /**
     * Helper to ensure the actor owns the record
     */
    private function findScoped(User $actor, string $uuid)
    {
        $model = $this->patientRepository->findByUuid($uuid);
        
        if (! $model) {
            abort(404, 'Resource not found');
        }

        if (! $actor->hasRole('superadmin')) {
            if ($model->company_id !== $actor->company_id) {
                abort(403, 'Unauthorized access to this resource.');
            }
        }
        return $model;
    }

    public function getPatient(User $actor, string $uuid)
    {
        $model = $this->findScoped($actor, $uuid);
        return new PatientResource($model);
    }

    public function updatePatient(User $actor, string $uuid, array $payload)
    {
        $this->findScoped($actor, $uuid);
        
        unset($payload['company_id']); 

        $model = $this->patientRepository->update($uuid, $payload);
        return new PatientResource($model);
    }

    public function deletePatient(User $actor, string $uuid)
    {
        $this->findScoped($actor, $uuid);
        $this->patientRepository->delete($uuid);
        return true;
    }

    public function restorePatient(User $actor, string $uuid)
    {
        $model = $this->patientRepository->restore($uuid);

        if (! $actor->hasRole('superadmin') && $model->company_id !== $actor->company_id) {
            $model->delete(); 
            abort(403, 'Unauthorized');
        }
        
        return new PatientResource($model);
    }
}