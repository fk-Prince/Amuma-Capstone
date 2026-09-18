<?php

use App\Enums\PermissionAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_permissions', function (Blueprint $table) {
            $table->id('employee_permission_id');
            $table->foreignId('employee_id')
                ->constrained('employees', 'employee_id');
            $table->foreignId('module_id')
                ->constrained('modules', 'module_id');
            $table->foreignId('branch_id')
                ->constrained('branches', 'branch_id');

            foreach (PermissionAction::columns() as $column) {
                $table->boolean($column)->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_permissions');
    }
};
