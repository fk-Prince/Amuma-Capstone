<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
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

            $table->boolean('can_read')->default(true);
            $table->boolean('can_create')->default(true);
            $table->boolean('can_update')->default(true);
            $table->boolean('can_approve')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_permissions');
    }
};
