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
        Schema::create('patient_admissions', function (Blueprint $table) {
            $table->id('patient_admission_id');

            $table->foreignId('bed_id')
                ->nullable()
                ->constrained('beds', 'bed_id')
                ->nullOnDelete();

            $table->foreignId('patient_id')
                ->constrained('patients', 'patient_id')
                ->cascadeOnDelete();

            $table->enum('status', [
                'waiting',
                'admitted',
                'discharged',
                'cancelled'
            ])->default('waiting');

            $table->string('note')->nullable();

            $table->timestamp('admitted_at')->nullable();
            $table->timestamp('end_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_admissions');
    }
};
