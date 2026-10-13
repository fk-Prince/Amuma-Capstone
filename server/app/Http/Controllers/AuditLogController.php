<?php

namespace App\Http\Controllers;

use App\Service\AuditLogService;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(private AuditLogService $auditLogService) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'branch_uuid' => ['required', 'string'],
            'module' => ['nullable', 'string', 'max:50'],
            'action' => ['nullable', 'string', 'max:50'],
            'search' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return $this->auditLogService->listAuditLogs($validated, $request->user());
    }

    public function show(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'branch_uuid' => ['required', 'string'],
        ]);

        return $this->auditLogService->showAuditLog($uuid, $validated, $request->user());
    }
}
