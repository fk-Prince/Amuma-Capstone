<?php

namespace App\Service;

use App\Repository\InvoiceRepository;
use App\Http\Resources\InvoiceResource;
use App\Models\User;

class InvoiceService
{
    private InvoiceRepository $invoiceRepository;

    public function __construct(InvoiceRepository $invoiceRepository) 
    {
        $this->invoiceRepository = $invoiceRepository;
    }

    public function createInvoice(User $actor, array $payload)
    {
        if (! $actor->hasRole('superadmin')) {
            $payload['company_id'] = $actor->company_id;
        }

        $model = $this->invoiceRepository->create($payload);
        return new InvoiceResource($model);
    }

    public function listInvoice(User $actor, int $perPage = 15)
    {
        $companyId = $actor->hasRole('superadmin') ? null : $actor->company_id;

        $collection = $this->invoiceRepository->paginate($perPage, $companyId);
        return InvoiceResource::collection($collection);
    }

    /**
     * Helper to ensure the actor owns the record
     */
    private function findScoped(User $actor, string $uuid)
    {
        $model = $this->invoiceRepository->findByUuid($uuid);
        
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

    public function getInvoice(User $actor, string $uuid)
    {
        $model = $this->findScoped($actor, $uuid);
        return new InvoiceResource($model);
    }

    public function updateInvoice(User $actor, string $uuid, array $payload)
    {
        $this->findScoped($actor, $uuid);
        
        unset($payload['company_id']); 

        $model = $this->invoiceRepository->update($uuid, $payload);
        return new InvoiceResource($model);
    }

    public function deleteInvoice(User $actor, string $uuid)
    {
        $this->findScoped($actor, $uuid);
        $this->invoiceRepository->delete($uuid);
        return true;
    }

    public function restoreInvoice(User $actor, string $uuid)
    {
        $model = $this->invoiceRepository->restore($uuid);

        if (! $actor->hasRole('superadmin') && $model->company_id !== $actor->company_id) {
            $model->delete(); 
            abort(403, 'Unauthorized');
        }
        
        return new InvoiceResource($model);
    }
}