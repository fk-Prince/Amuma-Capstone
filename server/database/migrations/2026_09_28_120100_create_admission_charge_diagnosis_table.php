<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_charge_diagnosis', function (Blueprint $table) {
            $table->id('admission_charge_diagnosis_id');
            $table->foreignId('patient_admission_id')
                ->constrained('patient_admissions', 'patient_admission_id')
                ->cascadeOnDelete();
            $table->foreignId('patient_diagnosis_id')
                ->constrained('patient_diagnosis', 'patient_diagnosis_id')
                ->cascadeOnDelete();
            $table->foreignId('diagnosis_case_id')
                ->nullable()
                ->constrained('diagnosis_cases', 'diagnosis_case_id')
                ->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->timestamps();
            $table->unique(['patient_admission_id', 'patient_diagnosis_id'], 'admission_charge_diagnosis_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_charge_diagnosis');
    }
};
