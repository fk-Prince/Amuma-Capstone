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
        Schema::create('employee_assigned_service', function (Blueprint $table) {
            $table->id('employee_assigned_service_id');
            $table->foreignId('employee_branch_id')
                ->constrained('employee_branches', 'employee_branch_id');
            $table->foreignId('service_id')
                ->constrained('services', 'service_id');
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_services');
    }
};
