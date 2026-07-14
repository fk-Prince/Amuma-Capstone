<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Repository\ModuleRepository;
use App\Http\Resources\ModuleResource;
use App\Models\User;
use App\Service\Utils\AuthGuard;

class ModuleService
{
    private ModuleRepository $moduleRepository;

    public function __construct(ModuleRepository $moduleRepository)
    {
        $this->moduleRepository = $moduleRepository;
    }

    public function listModule(array $payload, User $user)
    {
        AuthGuard::requireModule($user, false, ModuleEnum::EmployeeManagement, PermissionAction::Read);
        return $this->moduleRepository->getAllModules();
    }
}
