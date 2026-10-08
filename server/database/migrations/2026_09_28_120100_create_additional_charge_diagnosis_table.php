<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('additional_charge_diagnosis', function (Blueprint $table) {
            $table->id('additional_charge_diagnosis_id');
            $table->foreignId('additional_charge_id')
                ->unique()
                ->constrained('additional_charges', 'additional_charge_id')
                ->cascadeOnDelete();
            $table->foreignId('patient_diagnosis_id')
                ->constrained('patient_diagnosis', 'patient_diagnosis_id')
                ->cascadeOnDelete();
            $table->foreignId('diagnosis_case_id')
                ->nullable()
                ->constrained('diagnosis_cases', 'diagnosis_case_id')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('additional_charge_diagnosis');
    }
};
