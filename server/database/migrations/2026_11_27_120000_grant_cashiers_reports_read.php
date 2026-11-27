<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $moduleId = DB::table('modules')->where('module_name', 'Reports')->value('module_id');

        if (!$moduleId) {
            return;
        }

        $assignments = DB::table('employee_branches')
            ->where('role_name', 'cashier')
            ->get(['employee_id', 'branch_id']);

        foreach ($assignments as $assignment) {
            DB::table('employee_permissions')->updateOrInsert(
                [
                    'employee_id' => $assignment->employee_id,
                    'branch_id' => $assignment->branch_id,
                    'module_id' => $moduleId,
                ],
                ['can_read' => true],
            );
        }
    }

    public function down(): void
    {
        // Read access remains granted when application code rolls back.
    }
};
