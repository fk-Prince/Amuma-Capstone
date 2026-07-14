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
        Schema::create('employee_branches', function (Blueprint $table) {
            $table->id('employee_branch_id');
            $table->string('role_name');
            $table->enum('assignment_type', ['online', 'facility', 'both'])->nullable();
            $table->foreignId('branch_id')
                ->constrained('branches', 'branch_id');
            $table->foreignId('employee_id')
                ->constrained('employees', 'employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_branches');
    }
};
