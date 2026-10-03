<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caregiver_facility_shifts', function (Blueprint $table) {
            $table->id('caregiver_facility_shift_id');

            $table->foreignId('caregiver_id')
                ->constrained('employees', 'employee_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('admission_id')
                ->constrained('patient_admissions', 'patient_admission_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('note')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['admission_id', 'is_active']);
            $table->index(['caregiver_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caregiver_facility_shifts');
    }
};
