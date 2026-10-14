<?php

namespace App\Http\Controllers;

use App\Guard\BranchGuard;
use App\Service\EmployeeNoteService;
use Exception;
use Illuminate\Http\Request;

class EmployeeNoteController extends Controller
{
    public function __construct(private EmployeeNoteService $employeeNoteService) {}

    public function index(Request $request)
    {
        $this->resolveStaffBranch($request);

        return $this->employeeNoteService->list($request->all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_uuid' => ['required', 'uuid'],
            'schedule_code' => ['nullable', 'string', 'max:50'],
            'patient_uuid' => ['nullable', 'uuid'],
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $this->resolveStaffBranch($request);

        return $this->employeeNoteService->create($request->all(), $request->user());
    }

    public function update(Request $request, string $uuid)
    {
        $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $this->resolveStaffBranch($request);

        return $this->employeeNoteService->update($request->all(), $uuid);
    }

    public function destroy(Request $request, string $uuid)
    {
        $this->resolveStaffBranch($request);

        return $this->employeeNoteService->delete($request->all(), $uuid);
    }

    private function resolveStaffBranch(Request $request): void
    {
        $branch = BranchGuard::resolveBranch($request->branch_uuid);

        $isStaff = $request->user()?->employee
            ?->employeeBranch()
            ->where('branch_id', $branch->branch_id)
            ->exists();

        if (!$isStaff) {
            throw new Exception('Insufficient permissions', 403);
        }

        BranchGuard::mergeRequest($request, $branch);
    }
}
