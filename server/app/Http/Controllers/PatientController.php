<?php

namespace App\Http\Controllers;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Requests\Patient\Clinical\StoreDiagnosisRequest;
use App\Http\Requests\Patient\Clinical\UpdateAssessmentRequest;
use App\Http\Requests\Patient\Clinical\UpdateDiagnosisRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Service\PatientService;
use Illuminate\Http\Request;

class PatientController extends Controller
{

    public function __construct(private PatientService $patientService) {}

    public function index(Request $request)
    {
        // return [];
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        // AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Patients, PermissionAction::Read);
        BranchGuard::mergeRequest($request, $branch);
        return $this->patientService->retrievePatients($request->all(), $request->user());
    }

    public function show(Request $request, string $uuid)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);
        // AuthGuard::requireModule($request->user(), $branch->branch_id, ModuleEnum::Patients, PermissionAction::Read);
        BranchGuard::mergeRequest($request, $branch);
        return $this->patientService->showPatient($uuid);
    }

    public function update(UpdatePatientRequest $request, string $uuid)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);

        AuthGuard::requireModule(
            $request->user(),
            $branch->branch_id,
            ModuleEnum::Patients,
            PermissionAction::Update
        );

        $payload = $request->validated();
        $payload['avatar'] = $request->file('avatar');

        return $this->patientService->updatePatient(
            $uuid,
            $branch->branch_id,
            $payload
        );
    }

    public function storeDiagnosis(StoreDiagnosisRequest $request, string $uuid)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);

        AuthGuard::requireModule(
            $request->user(),
            $branch->branch_id,
            ModuleEnum::Patients,
            PermissionAction::Create
        );

        $validated = $request->validated();
        $validated['diagnosis_file'] = $request->file('diagnosis_file');

        return $this->patientService->addDiagnosis(
            $uuid,
            $branch->branch_id,
            $validated
        );
    }

    public function updateDiagnosis(UpdateDiagnosisRequest $request, string $uuid, string $diagnosisUuid)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);

        AuthGuard::requireModule(
            $request->user(),
            $branch->branch_id,
            ModuleEnum::Patients,
            PermissionAction::Update
        );

        $validated = $request->validated();
        $validated['diagnosis_file'] = $request->file('diagnosis_file');

        return $this->patientService->updateDiagnosis(
            $uuid,
            $branch->branch_id,
            $diagnosisUuid,
            $validated
        );
    }

    public function updateAssessment(UpdateAssessmentRequest $request, string $uuid, string $assessmentUuid)
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);

        AuthGuard::requireModule(
            $request->user(),
            $branch->branch_id,
            ModuleEnum::Patients,
            PermissionAction::Update
        );

        return $this->patientService->updateAssessment(
            $uuid,
            $branch->branch_id,
            $assessmentUuid,
            $request->validated()
        );
    }

    public function report(Request $request, string $uuid)
    {
        BranchGuard::resolveBranch($request->branch_uuid);

        $sections = $request->input('sections', []);

        if (is_string($sections)) {
            $sections = array_filter(array_map('trim', explode(',', $sections)));
        }

        return $this->patientService->buildPatientReport(
            $uuid,
            (array) $sections,
            $request->input('diagnosis_uuid')
        );
    }
}
