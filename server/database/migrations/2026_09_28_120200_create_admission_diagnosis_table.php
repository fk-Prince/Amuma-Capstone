<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_diagnosis', function (Blueprint $table) {
            $table->id('admission_diagnosis_id');
            $table->foreignId('admission_charge_diagnosis_id')
                ->constrained('admission_charge_diagnosis', 'admission_charge_diagnosis_id')
                ->cascadeOnDelete();
            $table->foreignId('patient_diagnosis_id')
                ->constrained('patient_diagnosis', 'patient_diagnosis_id')
                ->cascadeOnDelete();
            $table->foreignId('diagnosis_case_id')
                ->nullable()
                ->constrained('diagnosis_cases', 'diagnosis_case_id')
                ->nullOnDelete();
            $table->string('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('billed')->nullable();
            $table->boolean('is_active')->default(false);
            $table->date('date_billed')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_diagnosis');
    }
};
