<?php

namespace App\Service;

use App\Repository\ScheduleRepository;
use App\Http\Resources\ScheduleResource;
use App\Models\User;

class ScheduleService
{
    private ScheduleRepository $scheduleRepository;

    public function __construct(ScheduleRepository $scheduleRepository) 
    {
        $this->scheduleRepository = $scheduleRepository;
    }

    public function createSchedule(User $actor, array $payload)
    {
        if (! $actor->hasRole('superadmin')) {
            $payload['company_id'] = $actor->company_id;
        }

        $model = $this->scheduleRepository->create($payload);
        return new ScheduleResource($model);
    }

    public function listSchedule(User $actor, int $perPage = 15)
    {
        $companyId = $actor->hasRole('superadmin') ? null : $actor->company_id;

        $collection = $this->scheduleRepository->paginate($perPage, $companyId);
        return ScheduleResource::collection($collection);
    }

    /**
     * Helper to ensure the actor owns the record
     */
    private function findScoped(User $actor, string $uuid)
    {
        $model = $this->scheduleRepository->findByUuid($uuid);
        
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

    public function getSchedule(User $actor, string $uuid)
    {
        $model = $this->findScoped($actor, $uuid);
        return new ScheduleResource($model);
    }

    public function updateSchedule(User $actor, string $uuid, array $payload)
    {
        $this->findScoped($actor, $uuid);
        
        unset($payload['company_id']); 

        $model = $this->scheduleRepository->update($uuid, $payload);
        return new ScheduleResource($model);
    }

    public function deleteSchedule(User $actor, string $uuid)
    {
        $this->findScoped($actor, $uuid);
        $this->scheduleRepository->delete($uuid);
        return true;
    }

    public function restoreSchedule(User $actor, string $uuid)
    {
        $model = $this->scheduleRepository->restore($uuid);

        if (! $actor->hasRole('superadmin') && $model->company_id !== $actor->company_id) {
            $model->delete(); 
            abort(403, 'Unauthorized');
        }
        
        return new ScheduleResource($model);
    }
}