<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Requests\DiagnosisCase\DiagnosisCaseRequest;
use App\Service\DiagnosisCaseService;
use Illuminate\Http\Request;

class DiagnosisCaseController extends Controller
{
    public function __construct(private DiagnosisCaseService $diagnosisCases) {}

    public function index(Request $request)
    {
        $request->validate([
            'branch_uuid' => ['required', 'uuid'],
            'for' => ['nullable', 'in:charges'],
        ]);

        $branch = BranchGuard::resolveBranch($request->branch_uuid);

        $module = $request->input('for') === 'charges' ? ModuleEnum::Admissions : ModuleEnum::Contracts;

        AuthGuard::requireModule($request->user(), $branch->branch_id, $module, PermissionAction::Read);
        BranchGuard::mergeRequest($request, $branch);

        return $this->diagnosisCases->listForBranch($request->all());
    }

    public function store(DiagnosisCaseRequest $request)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Contracts, PermissionAction::Create);
        BranchGuard::mergeRequest($request, $branch);

        return $this->diagnosisCases->store($request->all());
    }

    public function update(DiagnosisCaseRequest $request, string $uuid)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Contracts, PermissionAction::Update);
        BranchGuard::mergeRequest($request, $branch);

        return $this->diagnosisCases->update($request->all(), $uuid);
    }
}
